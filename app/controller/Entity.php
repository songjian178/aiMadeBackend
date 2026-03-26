<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use think\facade\Db;

class Entity extends BaseController
{
    /**
     * 获取实体分类列表（仅返回在线可用）
     * @return \think\Response
     */
    public function categoryList()
    {
        $list = Db::name('entity_category')
            ->field('id,name,price,validity_period,render_count,description,image_url,sort_order')
            ->where('status', 1)
            ->where('is_display', 1)
            ->whereNull('deleted_at')
            ->order('sort_order', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        return $this->success($list, '获取实体分类成功');
    }

    /**
     * 获取实体分类列表（含当前用户可使用次数）
     * @return \think\Response
     */
    public function categoryListWithUsage()
    {
        $userId = $this->getCurrentUserId();

        $list = Db::name('entity_category')
            ->alias('ec')
            ->leftJoin('user_purchased_entity upe', "upe.category_id = ec.id AND upe.user_id = {$userId} AND upe.status = 1 AND upe.deleted_at IS NULL AND upe.expire_time > '" . date('Y-m-d H:i:s') . "'")
            ->field('ec.id,ec.name,ec.price,ec.validity_period,ec.render_count,ec.description,ec.image_url,ec.sort_order,IFNULL(SUM(upe.remaining_renders),0) as user_available_count')
            ->where('ec.status', 1)
            ->where('ec.is_display', 1)
            ->whereNull('ec.deleted_at')
            ->group('ec.id,ec.name,ec.price,ec.validity_period,ec.render_count,ec.description,ec.image_url,ec.sort_order')
            ->order('ec.sort_order', 'asc')
            ->order('ec.id', 'asc')
            ->select()
            ->toArray();

        return $this->success($list, '获取实体分类成功');
    }

    /**
     * 用户制作历史
     * 外层：用户当前购买的实体权益（未过期）
     * 内层：该权益下已生成的图片
     * @return \think\Response
     */
    public function makeHistory()
    {
        $userId = $this->getCurrentUserId();
        $now = date('Y-m-d H:i:s');

        $rows = Db::name('user_purchased_entity')
            ->alias('upe')
            ->join('entity_category ec', 'ec.id = upe.category_id', 'inner')
            ->leftJoin('entity_order o', 'o.id = upe.order_id AND o.user_id = upe.user_id AND o.status = 1 AND o.deleted_at IS NULL AND o.payment_status = 1')
            ->leftJoin('order_corpus oc', 'oc.order_id = o.id AND oc.status = 1 AND oc.deleted_at IS NULL')
            ->leftJoin('generated_image gi', 'gi.corpus_id = oc.id AND gi.status = 1 AND gi.deleted_at IS NULL')
            ->field('upe.id as purchased_entity_id,upe.category_id,upe.order_id,upe.expire_time,upe.remaining_renders,ec.name as category_name,' .
                'gi.image_url,gi.render_url,gi.corpus_id,oc.prompt')
            ->where('upe.user_id', $userId)
            ->where('upe.status', 1)
            ->whereNull('upe.deleted_at')
            ->where('upe.expire_time', '>', $now)
            ->where('ec.status', 1)
            ->whereNull('ec.deleted_at')
            ->order('upe.id', 'desc')
            ->select()
            ->toArray();

        // 以 purchased_entity_id 聚合二维结构
        $map = [];
        foreach ($rows as $row) {
            $key = (int)$row['purchased_entity_id'];
            if (!isset($map[$key])) {
                $map[$key] = [
                    'purchased_entity_id' => (int)$row['purchased_entity_id'],
                    'category_id' => (int)$row['category_id'],
                    'category_name' => (string)$row['category_name'],
                    'order_id' => (int)$row['order_id'],
                    'remaining_renders' => (int)($row['remaining_renders'] ?? 0),
                    'expire_time' => $row['expire_time'],
                    'images' => []
                ];
            }

            // 左连接时无图片：gi.corpus_id 可能为空
            $corpusId = $row['corpus_id'] ?? null;
            if ($corpusId !== null && $corpusId !== '') {
                $map[$key]['images'][] = [
                    'image_url' => $row['image_url'],
                    'render_url' => $row['render_url'],
                    'corpus_id' => (int)$row['corpus_id'],
                    'prompt' => $row['prompt']
                ];
            }
        }

        return $this->success(array_values($map), '获取用户制作历史成功');
    }
}
