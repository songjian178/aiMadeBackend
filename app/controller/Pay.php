<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\service\WechatPayService;
use think\facade\Db;
use think\facade\Log;
// use think\Request;
use think\App;

/**
 * 微信支付控制器
 */
class Pay extends BaseController
{
    // 此文件暂时没有作用!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

    protected $wechatPayService;
    
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->wechatPayService = new WechatPayService();
    }
    
    /**
     * 创建Native支付订单
     * 
     * @param 
     * @return \think\Response
     */
    public function createNativeOrder()
    {
        try {
            // 获取当前登录用户
            $userId = $this->getCurrentUserId();
            if (!$userId) {
                return $this->error('请先登录', 401);
            }
            // 获取请求参数
            $params = $this->request->post();
            
            // 参数验证
            if (empty($params['amount'])) {
                return $this->error('支付金额不能为空');
            }
            
            if (empty($params['description'])) {
                return $this->error('商品描述不能为空');
            }
            
            // 金额转换为分（微信要求单位为分）
            $amount = (int)($params['amount'] * 100);
            if ($amount <= 0) {
                return $this->error('支付金额必须大于0');
            }
            
            // 生成订单号
            $outTradeNo = $this->wechatPayService->generateOrderNo();
            
            // 构建附加数据
            $attach = json_encode([
                'user_id' => $userId,
                'order_type' => $params['order_type'] ?? 'default',
                'extra' => $params['extra'] ?? []
            ]);
            
            // 可选参数
            $extra = [
                'attach' => $attach,
            ];
            
            // 如果设置了过期时间
            if (!empty($params['expire_minutes'])) {
                $expireTime = date('c', strtotime("+{$params['expire_minutes']} minutes"));
                $extra['time_expire'] = $expireTime;
            }
            
            // 调用微信支付接口
            $result = $this->wechatPayService->nativePay(
                $outTradeNo,
                $amount,
                $params['description'],
                $extra
            );
            
            // 保存订单到数据库
            // $orderData = [
            //     'user_id' => $userId,
            //     'order_no' => $outTradeNo,
            //     'out_trade_no' => $outTradeNo,
            //     'total_amount' => $amount / 100, // 转换为元存储
            //     'description' => $params['description'],
            //     'attach' => $attach,
            //     'code_url' => $result['code_url'] ?? '',
            //     'status' => 'pending', // pending, paid, closed, expired
            //     'create_time' => date('Y-m-d H:i:s'),
            //     'expire_time' => !empty($params['expire_minutes']) 
            //         ? date('Y-m-d H:i:s', strtotime("+{$params['expire_minutes']} minutes"))
            //         : date('Y-m-d H:i:s', strtotime('+30 minutes'))
            // ];
            
            // Db::name('order')->insert($orderData);
            
            // 返回二维码链接
            return $this->success([
                'order_no' => $outTradeNo,
                'code_url' => $result['code_url'] ?? '',
                'amount' => $amount / 100,
                'description' => $params['description'],
                // 'expire_time' => $orderData['expire_time']
            ], '订单创建成功');
            
        } catch (\Exception $e) {
            Log::error('创建微信支付订单失败：' . $e->getMessage());
            return $this->error('创建订单失败：' . $e->getMessage());
        }
    }
    
    /**
     * 查询订单状态
     * 
     * @param 
     * @return \think\Response
     */
    public function queryOrder()
    {
        try {
            $userId = $this->getCurrentUserId();
            if (!$userId) {
                return $this->error('请先登录', 401);
            }
            
            $outTradeNo = $this->request->post('order_no');
            if (empty($outTradeNo)) {
                return $this->error('订单号不能为空');
            }
            
            // 查询本地订单
            $order = Db::name('order')
                ->where('order_no', $outTradeNo)
                ->where('user_id', $userId)
                ->find();
            
            if (!$order) {
                return $this->error('订单不存在');
            }
            
            // 如果订单已经是支付成功状态，直接返回
            if ($order['status'] === 'paid') {
                return $this->success([
                    'order_no' => $outTradeNo,
                    'status' => 'paid',
                    'pay_time' => $order['pay_time'],
                    'transaction_id' => $order['transaction_id'] ?? ''
                ], '订单已支付');
            }
            
            // 查询微信支付订单状态
            $wxResult = $this->wechatPayService->queryOrder($outTradeNo);
            
            // 更新本地订单状态
            if ($wxResult['trade_state'] === 'SUCCESS') {
                Db::name('order')
                    ->where('order_no', $outTradeNo)
                    ->update([
                        'status' => 'paid',
                        'pay_time' => date('Y-m-d H:i:s', strtotime($wxResult['success_time'])),
                        'transaction_id' => $wxResult['transaction_id'] ?? '',
                        'update_time' => date('Y-m-d H:i:s')
                    ]);
            }
            
            return $this->success([
                'order_no' => $outTradeNo,
                'status' => $this->mapTradeState($wxResult['trade_state']),
                'trade_state_desc' => $wxResult['trade_state_desc'] ?? '',
                'amount' => ($wxResult['amount']['total'] ?? 0) / 100,
                'payer' => $wxResult['payer'] ?? []
            ], '查询成功');
            
        } catch (\Exception $e) {
            Log::error('查询微信支付订单失败：' . $e->getMessage());
            return $this->error('查询订单失败：' . $e->getMessage());
        }
    }
    
    /**
     * 关闭订单
     * 
     * @param 
     * @return \think\Response
     */
    public function closeOrder()
    {
        try {
            $userId = $this->getCurrentUserId();
            if (!$userId) {
                return $this->error('请先登录', 401);
            }
            
            $outTradeNo = $this->request->post('order_no');
            if (empty($outTradeNo)) {
                return $this->error('订单号不能为空');
            }
            
            // 查询本地订单
            $order = Db::name('order')
                ->where('order_no', $outTradeNo)
                ->where('user_id', $userId)
                ->find();
            
            if (!$order) {
                return $this->error('订单不存在');
            }
            
            if ($order['status'] === 'paid') {
                return $this->error('订单已支付，无法关闭');
            }
            
            // 调用微信支付关闭订单接口
            $this->wechatPayService->closeOrder($outTradeNo);
            
            // 更新本地订单状态
            Db::name('order')
                ->where('order_no', $outTradeNo)
                ->update([
                    'status' => 'closed',
                    'update_time' => date('Y-m-d H:i:s')
                ]);
            
            return $this->success(null, '订单关闭成功');
            
        } catch (\Exception $e) {
            Log::error('关闭微信支付订单失败：' . $e->getMessage());
            return $this->error('关闭订单失败：' . $e->getMessage());
        }
    }
    
    /**
     * 申请退款
     * 
     * @param 
     * @return \think\Response
     */
    public function refund()
    {
        try {
            $userId = $this->getCurrentUserId();
            if (!$userId) {
                return $this->error('请先登录', 401);
            }
            
            $params = $this->request->post();
            
            if (empty($params['order_no'])) {
                return $this->error('订单号不能为空');
            }
            
            if (empty($params['refund_amount'])) {
                return $this->error('退款金额不能为空');
            }
            
            // 查询本地订单
            $order = Db::name('order')
                ->where('order_no', $params['order_no'])
                ->where('user_id', $userId)
                ->find();
            
            if (!$order) {
                return $this->error('订单不存在');
            }
            
            if ($order['status'] !== 'paid') {
                return $this->error('订单未支付，无法退款');
            }
            
            // 生成退款单号
            $outRefundNo = 'REF' . $this->wechatPayService->generateOrderNo();
            
            // 金额转换为分
            $refundAmount = (int)($params['refund_amount'] * 100);
            $totalAmount = (int)($order['total_amount'] * 100);
            
            if ($refundAmount <= 0 || $refundAmount > $totalAmount) {
                return $this->error('退款金额无效');
            }
            
            // 调用微信退款接口
            $result = $this->wechatPayService->refund(
                $outRefundNo,
                $params['order_no'],
                $refundAmount,
                $totalAmount,
                $params['reason'] ?? ''
            );
            
            // 保存退款记录
            Db::name('refund')->insert([
                'order_no' => $params['order_no'],
                'out_refund_no' => $outRefundNo,
                'refund_id' => $result['refund_id'] ?? '',
                'user_id' => $userId,
                'refund_amount' => $params['refund_amount'],
                'reason' => $params['reason'] ?? '',
                'status' => $result['status'] ?? 'PROCESSING',
                'create_time' => date('Y-m-d H:i:s')
            ]);
            
            return $this->success([
                'out_refund_no' => $outRefundNo,
                'refund_id' => $result['refund_id'] ?? '',
                'status' => $result['status'] ?? 'PROCESSING'
            ], '退款申请已提交');
            
        } catch (\Exception $e) {
            Log::error('微信支付退款失败：' . $e->getMessage());
            return $this->error('退款失败：' . $e->getMessage());
        }
    }
    
    /**
     * 微信支付回调通知
     * 
     * @param 
     * @return \think\Response
     */
    public function notify()
    {
        try {
            $headers = $this->request->header();
            $body = $this->request->getContent();
            if ($body === '') {
                $body = (string)$this->request->getInput();
            }

            Log::info('微信支付回调通知：headers=' . json_encode($headers) . ', body=' . $body);

            $resource = [
                'algorithm' => 'AEAD_AES_256_GCM',
                'associated_data' => 'transaction',
                'ciphertext' => "rH/sYcmc8R7YLPWztH/q7Y25gfRuMu+EwsD+l2MHmMw0qNvIDleY2VIjOtRM5RKEBdhkXZHlJKeiztn+wea2SNP/9VyNoufOHjoITveDgtCGUWiCpg0lehnAxAVuYUesSkCLLXzTPacBr/RTLyF0ju+OVVhwu1gxyHfJDFIbtzt5YU1MSxNyRM/UmGaHWoBqM8pG8OK9I3Z4ELNSqJxeVvtfBOoKocU9bHrOYMM7pcdK8iNWgZ9l2AiVmxO3hFAILhLlGy4m0vt5MKIZA/k5r8BssOMaWq7EXYi0h/POADtlPBKq6qUsdCFWhHSY1Ib2XHwa30L9zegYYyiUhMRT8DgNNttjUsttmsc4ec/P9ZVCW8j2QQpzRoiZRCHCafhPpfn8lTsNktyZSNqoVqbJwcGX9N/IUT03wUZHMwizgY0dcDxd8P8R+A1U3KLsvfCdtRJtGYWuFeknMIL3FU6Qeq5leqXpXLjz0W8QgJLzTCSVPu2OCB/tSqgmL4/+AxamyUehbU5wVqr1N6y0ISDDHgvNAvfrGa64WUquHMZ026hpMbdBkazInaKijDo3RZmm2rBpa70rAzt5n7Q7Kj9V+uQohrSQgI3Clorx40u8lLAHqAxIieepwta5B2icNwhnu73yHrcsJ78KaA==",
                'nonce' => "o66Hv7wQj12W",
            ];
            $notifyData = $this->wechatPayService->decryptNotify($resource);

            Log::info('微信支付回调解密数据：' . json_encode($notifyData));
            
            // 处理支付成功通知
            if ($notifyData['trade_state'] === 'SUCCESS') {
                $outTradeNo = $notifyData['out_trade_no'];
                $transactionId = $notifyData['transaction_id'];
                $successTime = $notifyData['success_time'];
                $amount = $notifyData['amount']['total'] ?? 0;
                $payer = $notifyData['payer'] ?? [];
                
                // 开启事务
                Db::startTrans();
                try {
                    // 查询订单
                    $order = Db::name('order')
                        ->where('order_no', $outTradeNo)
                        ->lock(true)
                        ->find();
                    
                    if ($order && $order['status'] !== 'paid') {
                        // 更新订单状态
                        Db::name('order')
                            ->where('order_no', $outTradeNo)
                            ->update([
                                'status' => 'paid',
                                'pay_time' => date('Y-m-d H:i:s', strtotime($successTime)),
                                'transaction_id' => $transactionId,
                                'payer_openid' => $payer['openid'] ?? '',
                                'update_time' => date('Y-m-d H:i:s')
                            ]);
                        
                        // 这里可以添加其他业务逻辑，如增加用户权益、发送通知等
                        // ...
                    }
                    
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    throw $e;
                }
            }
            
            // 返回成功响应给微信服务器
            return json(['code' => 'SUCCESS', 'message' => '成功']);
            
        } catch (\Exception $e) {
            Log::error('微信支付回调处理失败：' . $e->getMessage());
            // 返回失败响应，微信会重新发送通知
            return json(['code' => 'FAIL', 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * 映射微信支付状态到本地状态
     * 
     * @param string $tradeState
     * @return string
     */
    protected function mapTradeState(string $tradeState): string
    {
        $stateMap = [
            'SUCCESS' => 'paid',        // 支付成功
            'REFUND' => 'refunded',     // 转入退款
            'NOTPAY' => 'pending',      // 未支付
            'CLOSED' => 'closed',       // 已关闭
            'REVOKED' => 'revoked',     // 已撤销
            'USERPAYING' => 'paying',   // 用户支付中
            'PAYERROR' => 'error',      // 支付失败
        ];
        
        return $stateMap[$tradeState] ?? 'unknown';
    }
}
