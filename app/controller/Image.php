<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\enums\OrderStatusEnum;
use think\facade\Db;

class Image extends BaseController
{
    /**
     * 生成图片资格校验（占位）
     * @return \think\Response
     */
    public function generateImage()
    {
        $userId = $this->getCurrentUserId();
        $categoryId = (int)$this->request->post('category_id');
        if ($categoryId <= 0) {
            return $this->error('参数不完整');
        }

        $availableOrder = Db::name('entity_order')
            ->alias('o')
            ->join('user_purchased_entity upe', 'upe.order_id = o.id AND upe.user_id = o.user_id')
            ->field('o.id as order_id,o.order_no,o.order_status,upe.remaining_renders,upe.expire_time')
            ->where('o.user_id', $userId)
            ->where('o.category_id', $categoryId)
            ->where('o.payment_status', 1)
            ->where('o.order_status', 'in', [0, 1])
            ->where('o.status', 1)
            ->whereNull('o.deleted_at')
            ->where('upe.status', 1)
            ->whereNull('upe.deleted_at')
            ->where('upe.remaining_renders', '>', 0)
            ->where('upe.expire_time', '>', date('Y-m-d H:i:s'))
            ->order('o.id', 'desc')
            ->find();

        if (!$availableOrder) {
            return $this->error('当前无可用订单，请先购买或检查剩余生成次数');
        }

        $this->writeLog('generate_image_check', '用户通过生成图片资格校验', $userId);

        $orderStatus = (int)$availableOrder['order_status'];
        return $this->success([
            'order_id' => (int)$availableOrder['order_id'],
            'order_no' => (string)$availableOrder['order_no'],
            'order_status' => $orderStatus,
            'order_status_name' => OrderStatusEnum::getName($orderStatus),
            'remaining_renders' => (int)$availableOrder['remaining_renders'],
            'expire_time' => $availableOrder['expire_time'],
        ], '校验通过，可进行生成');
    }
}

