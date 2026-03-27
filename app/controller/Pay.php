<?php

namespace app\controller;

use app\BaseController;
use app\service\WechatPayService;

class Pay extends BaseController
{
    /**
     * 生成微信支付二维码
     * @return \think\response\Json
     */
    public function createQrCode()
    {
        try {
            $outTradeNo = $this->request->post('out_trade_no');
            $totalFee = $this->request->post('total_fee');
            $body = $this->request->post('body');
            $attach = $this->request->post('attach', '');

            // 验证参数
            if (empty($outTradeNo) || empty($totalFee) || empty($body)) {
                return $this->error('参数错误', 400);
            }

            // 转换金额为分
            $totalFee = intval($totalFee * 100);

            // 生成支付二维码
            $wechatPayService = new WechatPayService();
            $result = $wechatPayService->createQrCode($outTradeNo, $totalFee, $body, $attach);

            return $this->success($result, '生成支付二维码成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * 处理微信支付回调
     * @return string
     */
    public function notify()
    {
        try {
            // 获取回调数据
            $xmlData = file_get_contents('php://input');

            // 处理回调
            $wechatPayService = new WechatPayService();
            $result = $wechatPayService->handleNotify($xmlData);

            // 转换为XML返回
            $xml = '<xml>';
            foreach ($result as $key => $value) {
                $xml .= '<' . $key . '><![CDATA[' . $value . ']]></' . $key . '>';
            }
            $xml .= '</xml>';

            return $xml;
        } catch (\Exception $e) {
            // 记录错误日志
            \think\facade\Log::error('微信支付回调错误: ' . $e->getMessage());

            // 返回失败
            $xml = '<xml>';
            $xml .= '<return_code><![CDATA[FAIL]]></return_code>';
            $xml .= '<return_msg><![CDATA[系统错误]]></return_msg>';
            $xml .= '</xml>';

            return $xml;
        }
    }
}
