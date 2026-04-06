<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\enums\OrderStatusEnum;
use app\service\WechatPayService;
use app\support\WechatPayCallbackTestSample;
use think\facade\Db;

class Order extends BaseController
{
    /** Native 下单：支付单有效时间（分钟），由后端固定，前端不传 */
    private const NATIVE_PAY_EXPIRE_MINUTES = 10;

    /**
     * 生成订单二维码（微信 Native：返回真实 code_url）
     * @return \think\Response
     */
    public function createPayQrCode()
    {
        $userId = $this->getCurrentUserId();

        $categoryId = (int)$this->request->post('category_id');
        if ($categoryId <= 0) {
            return $this->error('参数不完整');
        }

        // 若用户已购买该分类且订单处于“待使用/生成中”，则不再返回支付二维码
        $existingUseable = Db::name('entity_order')
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

        if ($existingUseable) {
            return $this->success([
                // 'order_id' => (int)$existingUseable['order_id'],
                'order_no' => (string)$existingUseable['order_no'],
                // 'order_status' => (int)$existingUseable['order_status'],
                // 'order_status_name' => OrderStatusEnum::getName((int)$existingUseable['order_status']),
                // 'remaining_renders' => (int)$existingUseable['remaining_renders'],
                // 'expire_time' => $existingUseable['expire_time']
            ], '已有可用订单，无需支付');
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
        $description = '爱制-' . (string)($category['name'] ?? '实体权益');
        $amountFen = (int)round(((float)$category['price']) * 100);
        if ($amountFen <= 0) {
            return $this->error('订单金额无效');
        }

        $expireMinutes = self::NATIVE_PAY_EXPIRE_MINUTES;
        $expireTime = date('Y-m-d H:i:s', strtotime("+{$expireMinutes} minutes"));
        $timeExpireRfc3339 = date('c', strtotime("+{$expireMinutes} minutes"));

        $attach = json_encode([
            'order_type' => 'wx',
            'extra' => $orderNo,
        ], JSON_UNESCAPED_UNICODE);

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

            $wechatPay = new WechatPayService();
            $wxResult = $wechatPay->nativePay(
                $orderNo,
                $amountFen,
                $description,
                [
                    'attach' => $attach,
                    'time_expire' => $timeExpireRfc3339,
                ]
            );

            $codeUrl = (string)($wxResult['code_url'] ?? '');
            if ($codeUrl === '') {
                throw new \RuntimeException('微信下单未返回二维码链接');
            }

            Db::commit();

            $this->writeLog('order_create_qr', '用户生成微信支付二维码，订单号：' . $orderNo, $userId);

            return $this->success([
                'order_id' => $orderId,
                'order_no' => $orderNo,
                'payment_method' => 'WX',
                'qr_code_url' => $codeUrl,
                'amount' => (float)$category['price'],
                'description' => $description,
                'expire_time' => $expireTime,
            ], '订单二维码生成成功');
        } catch (\Throwable $e) {
            Db::rollback();
            $this->writeLog('order_create_qr', '生成微信支付二维码失败：' . $e->getMessage(), $userId, 3);
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

        $orderIds = array_map(static fn($row) => (int)$row['order_id'], $rows);
        $usedRenderMap = [];
        if (!empty($orderIds)) {
            $usedRows = Db::name('order_corpus')->alias('oc')
                ->join('generated_image gi', 'gi.corpus_id = oc.id')
                ->field('oc.order_id,gi.render_url')
                ->whereIn('oc.order_id', $orderIds)
                ->where('oc.status', 1)
                ->whereNull('oc.deleted_at')
                ->where('gi.status', 1)
                ->whereNull('gi.deleted_at')
                ->where('gi.is_use', 1)
                ->order('gi.id', 'desc')
                ->select()
                ->toArray();

            foreach ($usedRows as $usedRow) {
                $oid = (int)$usedRow['order_id'];
                if (!isset($usedRenderMap[$oid])) {
                    $usedRenderMap[$oid] = $usedRow['render_url'] !== null ? (string)$usedRow['render_url'] : null;
                }
            }
        }

        $list = [];
        foreach ($rows as $row) {
            $initial = (int)($row['initial_render_count'] ?? 0);
            $remaining = (int)($row['remaining_renders'] ?? 0);
            $used = max(0, $initial - $remaining);

            $orderStatus = (int)($row['order_status'] ?? 0);
            $orderId = (int)$row['order_id'];
            $usedRenderUrl = in_array($orderStatus, [2, 3, 4, 5], true)
                ? ($usedRenderMap[$orderId] ?? null)
                : null;
            $list[] = [
                'order_id' => $orderId,
                'order_no' => (string)$row['order_no'],
                'category_name' => (string)$row['category_name'],
                'initial_render_count' => $initial,
                'used_render_count' => $used,
                'remaining_render_count' => $remaining,
                'purchase_time' => $row['purchase_time'],
                'expire_time' => $row['expire_time'],
                'order_status' => $orderStatus,
                'order_status_name' => OrderStatusEnum::getName($orderStatus),
                'total_amount' => (string)$row['total_amount'],
                'render_url' => $usedRenderUrl,
            ];
        }

        return $this->success($list, '获取订单列表成功');
    }

    /**
     * 获取订单状态列表（按订单ID）
     * @return \think\Response
     */
    public function statusList()
    {
        $userId = $this->getCurrentUserId();
        $orderId = (int)$this->request->get('order_id', (int)$this->request->post('order_id', 0));

        if ($orderId <= 0) {
            return $this->error('参数不完整');
        }

        // 校验订单归属
        $order = Db::name('entity_order')
            ->field('id,user_id')
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->find();
        if (!$order) {
            return $this->error('订单不存在');
        }

        $rows = Db::name('order_status')
            ->where('order_id', $orderId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        $list = [];
        foreach ($rows as $row) {
            $status = (int)($row['status'] ?? 0);
            $list[] = [
                'id' => (int)$row['id'],
                'order_id' => (int)$row['order_id'],
                'status' => $status,
                'status_name' => OrderStatusEnum::getName($status),
                'remark' => $row['remark'] !== null ? (string)$row['remark'] : null,
                'created_at' => $row['created_at'] ?? null,
            ];
        }

        return $this->success($list, '获取订单状态列表成功');
    }

    /**
     * 基于渲染图下单
     * @return \think\Response
     */
    public function placeOrder()
    {
        $userId = $this->getCurrentUserId();
        $imageId = (int)$this->request->post('image_id', 0);
        $addressId = (int)$this->request->post('address_id', 0);
        $remark = trim((string)$this->request->post('remark', ''));
        $attributeValuesRaw = $this->request->post('attribute_values', []);

        if ($imageId <= 0 || $addressId <= 0) {
            return $this->error('参数不完整');
        }

        if (is_string($attributeValuesRaw)) {
            $decoded = json_decode($attributeValuesRaw, true);
            $attributeValuesRaw = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($attributeValuesRaw)) {
            return $this->error('属性参数格式错误');
        }

        // 地址必须属于当前用户且可用
        $address = Db::name('user_address')
            ->where('id', $addressId)
            ->where('user_id', $userId)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->find();
        if (!$address) {
            return $this->error('地址不存在或不可用');
        }

        // 通过 generated_image -> order_corpus -> entity_order 找到对应订单
        $target = Db::name('generated_image')->alias('gi')
            ->join('order_corpus oc', 'oc.id = gi.corpus_id')
            ->join('entity_order o', 'o.id = oc.order_id')
            ->field('gi.id as image_id,gi.is_use,gi.render_url,o.id as order_id,o.user_id,o.order_status,o.payment_status,o.category_id')
            ->where('gi.id', $imageId)
            ->where('gi.status', 1)
            ->whereNull('gi.deleted_at')
            ->whereNull('oc.deleted_at')
            ->whereNull('o.deleted_at')
            ->where('o.user_id', $userId)
            ->find();

        if (!$target) {
            return $this->error('图片记录不存在或无权限');
        }

        if ((int)$target['payment_status'] !== 1) {
            return $this->error('订单未支付，无法下单');
        }

        if (trim((string)($target['render_url'] ?? '')) === '') {
            return $this->error('渲染预览图尚未生成完成，无法下单');
        }

        // 处理并校验属性值选择（可选）
        $orderAttrRows = [];
        if (!empty($attributeValuesRaw)) {
            $normalized = [];
            foreach ($attributeValuesRaw as $item) {
                if (!is_array($item)) {
                    return $this->error('属性参数格式错误');
                }

                $attributeId = (int)($item['attribute_id'] ?? 0);
                $attributeValueId = (int)($item['attribute_value_id'] ?? 0);
                if ($attributeId <= 0 || $attributeValueId <= 0) {
                    return $this->error('属性参数格式错误');
                }

                // 同一属性重复传时，以最后一次为准
                $normalized[$attributeId] = $attributeValueId;
            }

            $valueIds = array_values($normalized);
            $validRows = Db::name('entity_attribute_value')->alias('eav')
                ->join('entity_attribute ea', 'ea.id = eav.attribute_id')
                ->field('ea.id as attribute_id,eav.id as attribute_value_id')
                ->whereIn('eav.id', $valueIds)
                ->where('ea.category_id', (int)$target['category_id'])
                ->where('ea.status', 1)
                ->whereNull('ea.deleted_at')
                ->where('eav.status', 1)
                ->whereNull('eav.deleted_at')
                ->select()
                ->toArray();

            $validMap = [];
            foreach ($validRows as $vr) {
                $validMap[(int)$vr['attribute_value_id']] = (int)$vr['attribute_id'];
            }

            foreach ($normalized as $attributeId => $attributeValueId) {
                if (!isset($validMap[$attributeValueId])) {
                    return $this->error('存在无效的属性值');
                }
                if ((int)$validMap[$attributeValueId] !== (int)$attributeId) {
                    return $this->error('属性与属性值不匹配');
                }

                $orderAttrRows[] = [
                    'order_id' => (int)$target['order_id'],
                    'attribute_id' => (int)$attributeId,
                    'attribute_value_id' => (int)$attributeValueId,
                    'status' => 1,
                ];
            }
        }

        Db::startTrans();
        try {
            // 1) 对应生成图标记为已用于下单
            Db::name('generated_image')
                ->where('id', $imageId)
                ->update(['is_use' => 1]);

            // 2) 订单状态改为“2：下单”，并绑定收货地址
            Db::name('entity_order')
                ->where('id', (int)$target['order_id'])
                ->where('user_id', $userId)
                ->update([
                    'order_status' => 2,
                    'address_id' => $addressId,
                ]);

            // 3) 新增订单状态流水
            Db::name('order_status')->insert([
                'order_id' => (int)$target['order_id'],
                'user_id' => $userId,
                'status' => 2,
                'remark' => '用户提交下单，绑定地址ID：' . $addressId."，备注：".$remark,
            ]);

            // 4) 下单后将当前订单对应权益次数清零，后续不可继续使用
            Db::name('user_purchased_entity')
                ->where('order_id', (int)$target['order_id'])
                ->where('user_id', $userId)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->update([
                    // 标记权益不可再使用（生成次数清零 + 状态置为不可用）
                    'status' => 0,
                    'remaining_renders' => 0
                ]);

            // 5) 保存用户下单时选择的属性与属性值
            if (!empty($orderAttrRows)) {
                $selectedAttrIds = array_map(static fn($row) => (int)$row['attribute_id'], $orderAttrRows);

                // 未本次选中的历史属性行置为无效
                Db::name('order_attribute_value')
                    ->where('order_id', (int)$target['order_id'])
                    ->whereNull('deleted_at')
                    ->whereNotIn('attribute_id', $selectedAttrIds)
                    ->update(['status' => 0]);

                foreach ($orderAttrRows as $attrRow) {
                    $affected = Db::name('order_attribute_value')
                        ->where('order_id', (int)$target['order_id'])
                        ->where('attribute_id', (int)$attrRow['attribute_id'])
                        ->whereNull('deleted_at')
                        ->update([
                            'attribute_value_id' => (int)$attrRow['attribute_value_id'],
                            'status' => 1,
                        ]);

                    if ((int)$affected === 0) {
                        Db::name('order_attribute_value')->insert($attrRow);
                    }
                }
            }

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            $this->writeLog('order_place', '基于渲染图下单失败：' . $e->getMessage(), $userId, 3);
            return $this->error('下单失败，请稍后重试');
        }

        $this->writeLog('order_place', '用户基于渲染图下单成功，image_id=' . $imageId, $userId);

        return $this->success([
            'image_id' => $imageId,
            'order_id' => (int)$target['order_id'],
            'order_status' => 2,
            'order_status_name' => OrderStatusEnum::getName(2),
            'address_id' => $addressId,
        ], '下单成功');
    }

    /**
     * 微信支付回调（APIv3：验签 + 解密 resource，与 createPayQrCode 下单参数一致）
     * 下单 attach：{"order_type":"wx","extra":"<order_no>"}，回调用 out_trade_no / attach 与本地订单对齐。
     *
     * @return \think\Response
     */
    public function payCallback()
    {
        $rawBody = $this->request->post();

        try {
            $wechatPay = new WechatPayService();
            
            // 解析请求体获取resource数据
            $data = $rawBody;
            if (empty($data['resource'])) {
                throw new \Exception('回调数据格式错误，缺少resource字段');
            }
            
            // 调用解密方法
            $notifyData = $wechatPay->decryptNotify($data['resource']);
            
            // 验证解密结果
            if (empty($notifyData)) {
                throw new \Exception('解密结果为空');
            }
            
        } catch (\Throwable $e) {
            $this->writeLog('payment_callback', '微信回调验签/解密失败：' . $e->getMessage(), null, 3);
            return json(['code' => 'FAIL', 'message' => '验签或解密失败']);
        }

        if (($notifyData['trade_state'] ?? '') !== 'SUCCESS') {
            return json(['code' => 'SUCCESS', 'message' => '成功']);
        }

        $outTradeNo = (string)($notifyData['out_trade_no'] ?? '');
        $transactionId = (string)($notifyData['transaction_id'] ?? '');
        $amountFen = (int)($notifyData['amount']['total'] ?? 0);
        $successTimeRaw = (string)($notifyData['success_time'] ?? '');
        $attachRaw = (string)($notifyData['attach'] ?? '');

        if ($outTradeNo === '' || $transactionId === '' || $amountFen <= 0) {
            return json(['code' => 'FAIL', 'message' => '回调参数不完整']);
        }

        // 与下单时 attach 一致：order_type=wx，extra 为业务 order_no（等于 out_trade_no）
        if ($attachRaw !== '') {
            $attachData = json_decode($attachRaw, true);
            if (is_array($attachData)) {
                if (($attachData['order_type'] ?? '') !== 'wx') {
                    return json(['code' => 'FAIL', 'message' => 'attach 不匹配']);
                }
                if ((string)($attachData['extra'] ?? '') !== $outTradeNo) {
                    return json(['code' => 'FAIL', 'message' => 'attach.extra 与订单号不一致']);
                }
            }
        }

        $paymentTime = $successTimeRaw !== ''
            ? date('Y-m-d H:i:s', strtotime($successTimeRaw))
            : date('Y-m-d H:i:s');
        $callbackAmountYuan = number_format($amountFen / 100, 2, '.', '');

        Db::startTrans();
        $order = null;
        try {
            $order = Db::name('entity_order')
                ->where('order_no', $outTradeNo)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->lock(true)
                ->find();
            if (!$order) {
                Db::rollback();
                return json(['code' => 'FAIL', 'message' => '订单不存在']);
            }

            // 幂等：未改库，rollback 释放行锁即可
            if ((int)$order['payment_status'] === 1) {
                Db::rollback();
                return json(['code' => 'SUCCESS', 'message' => '成功']);
            }

            $orderAmountFen = (int)round(((float)$order['total_amount']) * 100);
            if ($orderAmountFen !== $amountFen) {
                Db::rollback();
                $this->writeLog(
                    'payment_callback',
                    '回调金额与订单不一致，order_no=' . $outTradeNo . ' local_fen=' . $orderAmountFen . ' wx_fen=' . $amountFen,
                    (int)$order['user_id'],
                    3
                );
                return json(['code' => 'FAIL', 'message' => '金额不一致']);
            }

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
                    'payment_status' => 1,
                ]);

            Db::name('order_status')->insert([
                'order_id' => (int)$order['id'],
                'user_id' => (int)$order['user_id'],
                'status' => 0,
                'remark' => '支付完成，订单进入待使用流程',
            ]);

            $expireTime = date('Y-m-d H:i:s', strtotime('+' . (int)$category['validity_period'] . ' days'));
            $userPurchasedEntityId = Db::name('user_purchased_entity')->insertGetId([
                'user_id' => (int)$order['user_id'],
                'category_id' => (int)$order['category_id'],
                'order_id' => (int)$order['id'],
                'expire_time' => $expireTime,
                'remaining_renders' => (int)$category['render_count'],
                'status' => 1,
            ]);

            Db::name('payment')->insert([
                'order_id' => (int)$order['id'],
                'user_purchased_entity_id' => $userPurchasedEntityId,
                'amount' => $callbackAmountYuan,
                'payment_method' => 'WX',
                'payment_transaction_id' => $transactionId,
                'payment_status' => 1,
                'payment_time' => $paymentTime,
                'refund_status' => 0,
                'status' => 1,
            ]);

            Db::name('log')->insert([
                'user_id' => (int)$order['user_id'],
                'operation_type' => 'payment_callback',
                'operation_ip' => $this->request->ip(),
                'user_agent' => (string)$this->request->header('user-agent', ''),
                'operation_content' => '微信支付回调成功，订单号：' . $outTradeNo . '，微信单号：' . $transactionId,
                'log_level' => 1,
                'status' => 1,
            ]);

            Db::commit();

            return json(['code' => 'SUCCESS', 'message' => '成功']);
        } catch (\Throwable $e) {
            Db::rollback();
            $this->writeLog(
                'payment_callback',
                '支付回调处理失败，订单号：' . $outTradeNo . '，错误：' . $e->getMessage(),
                is_array($order) ? (int)($order['user_id'] ?? 0) : null,
                3
            );
            return json(['code' => 'FAIL', 'message' => '处理失败']);
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

}
