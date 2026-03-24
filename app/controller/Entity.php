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
}
