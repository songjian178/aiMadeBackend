<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use think\facade\Db;

class Order extends BaseController
{
    /**
     * 生成订单二维码（测试）
     * @return \think\Response
     */
    public function createPayQrCode()
    {
        $userId = $this->getCurrentUserId();

        $categoryId = (int)$this->request->post('category_id');
        if ($categoryId <= 0) {
            return $this->error('参数不完整');
        }

        $category = Db::name('entity_category')
            ->where('id', $categoryId)
            ->where('status', 1)
            ->where('is_display', 1)
            ->whereNull('deleted_at')
            ->find();
        if (!$category) {
            return $this->error('实体分类不存在或不可用');
        }

        $orderNo = $this->generateOrderNo();
        Db::startTrans();
        try {
            $orderId = Db::name('entity_order')->insertGetId([
                'user_id' => $userId,
                'order_no' => $orderNo,
                'category_id' => $categoryId,
                'total_amount' => $category['price'],
                'payment_method' => 'WX',
                'payment_status' => 0,
                'order_status' => 0,
                'status' => 1
            ]);

            Db::commit();

            $this->writeLog('order_create_qr', '用户生成订单二维码', $userId);

            return $this->success([
                'order_id' => $orderId,
                'order_no' => $orderNo,
                'payment_method' => 'WX',
                'qr_code_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=WeChatPay_Fake_Link'
            ], '订单二维码生成成功');
        } catch (\Throwable $e) {
            Db::rollback();
            return $this->error('订单创建失败，请稍后重试');
        }
    }

    /**
     * 订单心跳检测（支付状态）
     * @return \think\Response
     */
    public function heartbeat()
    {
        $userId = $this->getCurrentUserId();
        $orderNo = (string)$this->request->post('order_no');
        if ($orderNo === '') {
            return $this->error('参数不完整');
        }

        $order = Db::name('entity_order')
            ->field('order_no,payment_status')
            ->where('order_no', $orderNo)
            ->where('user_id', $userId)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->find();
        if (!$order) {
            return $this->error('订单不存在');
        }

        $isPaid = (int)$order['payment_status'] === 1;
        return $this->success([
            'order_no' => $order['order_no'],
            'payment_status' => (int)$order['payment_status'],
            'result' => $isPaid ? '支付成功' : '支付失败'
        ], '心跳检测成功');
    }

    /**
     * 生成18位订单号：AM + YmdHis + 2位随机数
     * @return string
     */
    private function generateOrderNo(): string
    {
        do {
            $orderNo = 'AM' . date('YmdHis') . str_pad((string)random_int(0, 99), 2, '0', STR_PAD_LEFT);
            $exists = Db::name('entity_order')->where('order_no', $orderNo)->find();
        } while ($exists);

        return $orderNo;
    }
}
