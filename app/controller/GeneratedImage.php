<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use think\facade\Db;

class GeneratedImage extends BaseController
{
    /**
     * 获取当前分类下用户的已生成图片
     * 按要求筛选：aimade_entity_order.order_status = 1（生成中）
     *
     * @return \think\Response
     */
    public function listByCategory()
    {
        $userId = $this->getCurrentUserId();
        $categoryId = (int)$this->request->post('category_id');
        if ($categoryId <= 0) {
            return $this->error('参数不完整');
        }

        $rows = Db::name('entity_order')
            ->alias('o')
            ->join('order_corpus oc', 'oc.order_id = o.id')
            ->join('generated_image gi', 'gi.corpus_id = oc.id')
            ->field('gi.id as image_id,gi.image_url,gi.render_url,gi.corpus_id,oc.prompt')
            ->where('o.user_id', $userId)
            ->where('o.category_id', $categoryId)
            ->where('o.order_status', 1)
            ->where('o.payment_status', 1)
            ->where('o.status', 1)
            ->whereNull('o.deleted_at')
            ->where('oc.status', 1)
            ->whereNull('oc.deleted_at')
            ->where('gi.status', 1)
            ->whereNull('gi.deleted_at')
            ->order('gi.id', 'desc')
            ->select()
            ->toArray();

        $list = [];
        foreach ($rows as $row) {
            $list[] = [
                'image_id' => (int)$row['image_id'],
                'image_url' => $row['image_url'],
                'render_url' => $row['render_url'],
                'corpus_id' => (int)$row['corpus_id'],
                'prompt' => $row['prompt'],
            ];
        }

        return $this->success($list, '获取生成图片列表成功');
    }
}

