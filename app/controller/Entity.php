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
}
