<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use think\facade\Db;

class Community extends BaseController
{
    /**
     * 收藏社区收录图片
     * @return \think\Response
     */
    public function favorite()
    {
        $userId = $this->getCurrentUserId();
        if ($userId <= 0) {
            return $this->error('请先登录', 401);
        }

        $communityId = (int)$this->request->post('creative_community_id');
        if ($communityId <= 0) {
            return $this->error('参数不完整');
        }

        $community = Db::name('creative_community')
            ->where('id', $communityId)
            ->where('status', 1)
            ->where('is_public', 1)
            ->whereNull('deleted_at')
            ->find();
        if (!$community) {
            return $this->error('社区收录不存在或不可收藏');
        }

        $image = Db::name('generated_image')
            ->where('id', (int)$community['image_id'])
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->find();
        if (!$image) {
            return $this->error('关联图片不存在或不可用');
        }

        $existing = Db::name('user_creative_favorite')
            ->where('user_id', $userId)
            ->where('creative_community_id', $communityId)
            ->find();

        if ($existing && (int)$existing['status'] === 1 && $existing['deleted_at'] === null) {
            return $this->success(['id' => (int)$existing['id']], '已收藏');
        }

        Db::startTrans();
        try {
            if ($existing) {
                Db::name('user_creative_favorite')
                    ->where('id', (int)$existing['id'])
                    ->update([
                        'status' => 1,
                        'deleted_at' => null,
                    ]);
                $favoriteId = (int)$existing['id'];
            } else {
                $favoriteId = Db::name('user_creative_favorite')->insertGetId([
                    'user_id' => $userId,
                    'creative_community_id' => $communityId,
                    'status' => 1,
                ]);
            }

            Db::name('creative_community')
                ->where('id', $communityId)
                ->setInc('likes_count', 1);

            Db::commit();
            $this->writeLog('community_favorite', '收藏社区图片，creative_community_id=' . $communityId, (int)$userId);
            return $this->success(['id' => $favoriteId], '收藏成功');
        } catch (\Throwable $e) {
            Db::rollback();
            return $this->error('收藏失败，请稍后重试');
        }
    }

    /**
     * 获取当前用户收藏的社区图片
     * @return \think\Response
     */
    public function favoriteList()
    {
        $userId = $this->getCurrentUserId();
        if ($userId <= 0) {
            return $this->error('请先登录', 401);
        }

        $page = max(1, (int)$this->request->get('page', 1));
        $perPage = min(50, max(1, (int)$this->request->get('per_page', 10)));

        $base = Db::name('user_creative_favorite')->alias('f')
            ->join('creative_community cc', 'cc.id = f.creative_community_id')
            ->join('generated_image gi', 'gi.id = cc.image_id')
            ->where('f.user_id', $userId)
            ->where('f.status', 1)
            ->whereNull('f.deleted_at')
            ->where('cc.status', 1)
            ->whereNull('cc.deleted_at')
            ->where('gi.status', 1)
            ->whereNull('gi.deleted_at');

        $total = (int)$base->count();
        $offset = ($page - 1) * $perPage;
        $rows = Db::name('user_creative_favorite')->alias('f')
            ->join('creative_community cc', 'cc.id = f.creative_community_id')
            ->join('generated_image gi', 'gi.id = cc.image_id')
            ->where('f.user_id', $userId)
            ->where('f.status', 1)
            ->whereNull('f.deleted_at')
            ->where('cc.status', 1)
            ->whereNull('cc.deleted_at')
            ->where('gi.status', 1)
            ->whereNull('gi.deleted_at')
            ->field('f.id as favorite_id, cc.id as creative_community_id, gi.id as image_id, gi.image_url, gi.render_url, cc.description')
            ->order('f.created_at', 'desc')
            ->limit($offset, $perPage)
            ->select()
            ->toArray();

        $list = [];
        foreach ($rows as $row) {
            $list[] = [
                'favorite_id' => (int)$row['favorite_id'],
                'creative_community_id' => (int)$row['creative_community_id'],
                'image_id' => (int)$row['image_id'],
                'image_url' => (string)$row['image_url'],
                'render_url' => $row['render_url'] !== null ? (string)$row['render_url'] : null,
                'description' => $row['description'] !== null ? (string)$row['description'] : null,
            ];
        }

        return $this->success([
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ], '获取收藏列表成功');
    }
}
