<?php

namespace app\service;

use think\Exception;
use think\facade\Log;

class WechatPayService
{
    private $appId;
    private $mchId;
    private $apiKey;
    private $notifyUrl;
    private $tradeType = 'NATIVE'; // 扫码支付

    public function __construct()
    {
        $this->appId = env('WECHAT_PAY_APPID', '');
        $this->mchId = env('WECHAT_PAY_MCHID', '');
        $this->apiKey = env('WECHAT_PAY_API_KEY', '');
        $this->notifyUrl = env('WECHAT_PAY_NOTIFY_URL', '');
    }

    /**
     * 生成微信支付二维码
     * @param string $outTradeNo 商户订单号
     * @param float $totalFee 订单金额（分）
     * @param string $body 商品描述
     * @param string $attach 附加数据
     * @return array
     * @throws Exception
     */
    public function createQrCode($outTradeNo, $totalFee, $body, $attach = '')
    {
        // 记录请求参数
        Log::info('微信支付请求参数', [
            'out_trade_no' => $outTradeNo,
            'total_fee' => $totalFee,
            'body' => $body,
            'attach' => $attach
        ]);

        // 验证配置
        if (empty($this->appId) || empty($this->mchId) || empty($this->apiKey)) {
            throw new Exception('微信支付配置不完整，请检查配置文件');
        }

        $params = [
            'appid' => $this->appId,
            'mch_id' => $this->mchId,
            'nonce_str' => $this->createNonceStr(),
            'body' => $body,
            'attach' => $attach,
            'out_trade_no' => $outTradeNo,
            'total_fee' => $totalFee,
            'spbill_create_ip' => $this->getClientIp(),
            'notify_url' => $this->notifyUrl,
            'trade_type' => $this->tradeType
        ];

        // 生成签名
        $params['sign'] = $this->generateSign($params);

        // 记录签名后的参数
        Log::info('微信支付签名后的参数', $params);

        // 转换为XML
        $xml = $this->arrayToXml($params);

        // 记录XML数据
        Log::info('微信支付XML数据: ' . $xml);

        // 发送请求
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $response = $this->postXmlCurl($xml, $url);

        // 记录响应数据
        Log::info('微信支付响应数据: ' . $response);

        // 转换为数组
        $result = $this->xmlToArray($response);

        // 检查返回结果
        if ($result['return_code'] != 'SUCCESS') {
            Log::error('微信支付返回失败: ' . $result['return_msg']);
            throw new Exception($result['return_msg']);
        }

        if ($result['result_code'] != 'SUCCESS') {
            Log::error('微信支付业务失败: ' . $result['err_code_des']);
            throw new Exception($result['err_code_des']);
        }

        return [
            'code_url' => $result['code_url'], // 二维码链接
            'out_trade_no' => $outTradeNo
        ];
    }

    /**
     * 处理微信支付回调
     * @param string $xmlData 微信回调数据
     * @return array
     */
    public function handleNotify($xmlData)
    {
        // 转换为数组
        $data = $this->xmlToArray($xmlData);

        // 验证签名
        if (!$this->verifySign($data)) {
            return [
                'return_code' => 'FAIL',
                'return_msg' => '签名验证失败'
            ];
        }

        // 检查支付结果
        if ($data['return_code'] != 'SUCCESS') {
            return [
                'return_code' => 'FAIL',
                'return_msg' => $data['return_msg']
            ];
        }

        if ($data['result_code'] != 'SUCCESS') {
            return [
                'return_code' => 'FAIL',
                'return_msg' => $data['err_code_des']
            ];
        }

        // 处理业务逻辑
        // TODO: 这里可以处理订单状态更新等业务逻辑

        return [
            'return_code' => 'SUCCESS',
            'return_msg' => 'OK'
        ];
    }

    /**
     * 生成签名
     * @param array $params 参数数组
     * @return string
     */
    private function generateSign($params)
    {
        // 过滤空值和sign字段
        $filteredParams = [];
        foreach ($params as $key => $value) {
            if ($key != 'sign' && $value !== '' && $value !== null) {
                $filteredParams[$key] = $value;
            }
        }

        // 排序参数
        ksort($filteredParams);

        // 拼接字符串
        $string = '';
        foreach ($filteredParams as $key => $value) {
            $string .= $key . '=' . $value . '&';
        }
        $string .= 'key=' . $this->apiKey;

        // 记录签名字符串用于调试
        Log::info('微信支付签名字符串: ' . $string);

        return strtoupper(md5($string));
    }

    /**
     * 验证签名
     * @param array $params 参数数组
     * @return bool
     */
    private function verifySign($params)
    {
        $sign = $params['sign'];
        unset($params['sign']);
        $newSign = $this->generateSign($params);
        return $sign == $newSign;
    }

    /**
     * 数组转XML
     * @param array $data 数组
     * @return string
     */
    private function arrayToXml($data)
    {
        $xml = '<xml>';
        foreach ($data as $key => $value) {
            if (is_numeric($value)) {
                $xml .= '<' . $key . '>' . $value . '</' . $key . '>';
            } else {
                $xml .= '<' . $key . '><![CDATA[' . $value . ']]></' . $key . '>';
            }
        }
        $xml .= '</xml>';
        return $xml;
    }

    /**
     * XML转数组
     * @param string $xml XML字符串
     * @return array
     */
    private function xmlToArray($xml)
    {
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }

    /**
     * 发送POST请求
     * @param string $xml XML数据
     * @param string $url 请求地址
     * @param int $second 超时时间
     * @return string
     */
    private function postXmlCurl($xml, $url, $second = 30)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    /**
     * 生成随机字符串
     * @param int $length 字符串长度
     * @return string
     */
    private function createNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 获取客户端IP
     * @return string
     */
    private function getClientIp()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
}
