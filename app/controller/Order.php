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
     * 我的订单列表（仅已支付订单，连表购买实体权益）
     * @return \think\Response
     */
    public function myList()
    {
        $userId = $this->getCurrentUserId();

        $rows = Db::name('entity_order')
            ->alias('o')
            ->join('user_purchased_entity upe', 'upe.order_id = o.id AND upe.user_id = o.user_id')
            ->join('entity_category c', 'c.id = o.category_id')
            ->field('o.id as order_id,o.order_no,o.order_status,o.total_amount,c.name as category_name,c.render_count as initial_render_count,upe.remaining_renders,upe.expire_time,upe.created_at as purchase_time')
            ->where('o.user_id', $userId)
            ->where('o.payment_status', 1)
            ->where('o.status', 1)
            ->whereNull('o.deleted_at')
            ->where('upe.status', 1)
            ->whereNull('upe.deleted_at')
            ->where('c.status', 1)
            ->whereNull('c.deleted_at')
            ->order('o.id', 'desc')
            ->select()
            ->toArray();

        $list = [];
        foreach ($rows as $row) {
            $initial = (int)($row['initial_render_count'] ?? 0);
            $remaining = (int)($row['remaining_renders'] ?? 0);
            $used = max(0, $initial - $remaining);

            $orderStatus = (int)($row['order_status'] ?? 0);
            $list[] = [
                'order_id' => (int)$row['order_id'],
                'order_no' => (string)$row['order_no'],
                'category_name' => (string)$row['category_name'],
                'initial_render_count' => $initial,
                'used_render_count' => $used,
                'remaining_render_count' => $remaining,
                'purchase_time' => $row['purchase_time'],
                'expire_time' => $row['expire_time'],
                'order_status' => $orderStatus,
                'order_status_name' => $this->resolveOrderStatusName($orderStatus),
                'total_amount' => (string)$row['total_amount'],
            ];
        }

        return $this->success($list, '获取订单列表成功');
    }

    /**
     * 支付回调（当前使用伪造回调数据）
     * @return \think\Response
     */
    public function payCallback() 
    {
        // 目前没有第三方平台回调，先伪造一份回调数据
        $callbackData = [
            'order_no' => (string)$this->request->post('order_no', ''),
            'payment_transaction_id' => (string)$this->request->post('payment_transaction_id', ''),
            'payment_method' => (string)$this->request->post('payment_method', 'WX'),
            'amount' => (string)$this->request->post('amount', '59.9'),
            'payment_status' => (int)$this->request->post('payment_status', 1),
            'payment_time' => (string)$this->request->post('payment_time', date('Y-m-d H:i:s'))
        ];

        if ($callbackData['order_no'] === '' || $callbackData['amount'] === '') {
            return $this->error('回调参数不完整');
        }

        // if ($callbackData['payment_status'] !== 1) {
        //     return $this->error('当前仅支持支付成功回调');
        // }

        $order = Db::name('entity_order')
            ->where('order_no', $callbackData['order_no'])
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->find();
        if (!$order) {
            return $this->error('订单不存在');
        }

        // 幂等：订单已支付时，避免重复记账
        if ((int)$order['payment_status'] === 1) {
            return $this->success([
                'order_no' => $callbackData['order_no']
            ], '订单已处理');
        }

        $orderAmount = number_format((float)$order['total_amount'], 2, '.', '');
        $callbackAmount = number_format((float)$callbackData['amount'], 2, '.', '');
        if ($orderAmount !== $callbackAmount) {
            return $this->error('回调金额与订单金额不一致');
        }

        Db::startTrans();
        try {
            $category = Db::name('entity_category')
                ->where('id', (int)$order['category_id'])
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->find();
            if (!$category) {
                throw new \RuntimeException('实体分类不存在或不可用');
            }

            Db::name('entity_order')
                ->where('id', (int)$order['id'])
                ->update([
                    'payment_status' => 1
                ]);

            Db::name('order_status')->insert([
                'order_id' => (int)$order['id'],
                'user_id' => (int)$order['user_id'],
                'status' => 0,
                'remark' => '支付完成，订单进入待使用流程'
            ]);

            $expireTime = date('Y-m-d H:i:s', strtotime('+' . (int)$category['validity_period'] . ' days'));
            $userPurchasedEntityId = Db::name('user_purchased_entity')->insertGetId([
                'user_id' => (int)$order['user_id'],
                'category_id' => (int)$order['category_id'],
                'order_id' => (int)$order['id'],
                'expire_time' => $expireTime,
                'remaining_renders' => (int)$category['render_count'],
                'status' => 1
            ]);

            Db::name('payment')->insert([
                'order_id' => (int)$order['id'],
                'user_purchased_entity_id' => $userPurchasedEntityId,
                'amount' => $callbackAmount,
                'payment_method' => $callbackData['payment_method'],
                'payment_transaction_id' => $callbackData['payment_transaction_id'],
                'payment_status' => 1,
                'payment_time' => $callbackData['payment_time'],
                'refund_status' => 0,
                'status' => 1
            ]);

            Db::name('log')->insert([
                'user_id' => (int)$order['user_id'],
                'operation_type' => 'payment_callback',
                'operation_ip' => $this->request->ip(),
                'user_agent' => $this->request->header('user-agent', ''),
                'operation_content' => '支付回调处理成功，订单号：' . $callbackData['order_no'],
                'log_level' => 1,
                'status' => 1
            ]);

            Db::commit();

            return $this->success([
                'order_no' => $callbackData['order_no'],
                'payment_status' => $callbackData['payment_status']
            ], '支付回调处理成功');
        } catch (\Throwable $e) {
            Db::rollback();
            $this->writeLog(
                'payment_callback',
                '支付回调处理失败，订单号：' . $callbackData['order_no'] . '，错误：' . $e->getMessage(),
                (int)$order['user_id'],
                3
            );
            return $this->error('支付回调处理失败，请稍后重试');
        }
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

    /**
     * 订单流程状态码 -> 展示名称（与 dataBase.md 中 order_status 一致）
     */
    private function resolveOrderStatusName(int $status): string
    {
        return match ($status) {
            0 => '待使用',
            1 => '生成中',
            2 => '下单',
            3 => '打样',
            4 => '生产',
            5 => '发货',
            default => '未知状态',
        };
    }
}
