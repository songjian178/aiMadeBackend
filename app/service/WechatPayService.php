<?php
declare (strict_types = 1);

namespace app\service;

use think\Exception;

/**
 * 微信支付服务类 - APIv3版本
 * 
 * 微信支付APIv3文档：https://pay.weixin.qq.com/doc/v3/merchant/4012791877
 * 签名规则文档：https://pay.weixin.qq.com/doc/v3/merchant/4012365342
 */
class WechatPayService
{
    // AES认证标签长度
    const AUTH_TAG_LENGTH_BYTE = 16;
    
    // 微信支付APIv3基础URL
    protected $baseUrl = 'https://api.mch.weixin.qq.com';
    
    // 商户配置
    protected $appId;
    protected $mchId;
    protected $apiKey;
    protected $notifyUrl;
    
    // API证书路径
    protected $certPath;
    protected $keyPath;
    
    /**
     * 构造方法
     */
    public function __construct()
    {
        // 优先从配置文件获取，兼容非框架环境
        if (function_exists('config')) {
            $this->appId = config('wechat_pay.appid') ?: env('WECHAT_PAY_APPID', '');
            $this->mchId = config('wechat_pay.mchid') ?: env('WECHAT_PAY_MCHID', '');
            $this->apiKey = config('wechat_pay.api_key') ?: env('WECHAT_PAY_API_KEY', '');
            $this->notifyUrl = config('wechat_pay.notify_url') ?: env('WECHAT_PAY_NOTIFY_URL', '');
            $this->certPath = config('wechat_pay.cert_path') ?: env('WECHAT_PAY_CERT_PATH', '');
            $this->keyPath = config('wechat_pay.key_path') ?: env('WECHAT_PAY_KEY_PATH', '');
        } else {
            // 非框架环境，直接从环境变量获取
            $this->appId = getenv('WECHAT_PAY_APPID') ?: '';
            $this->mchId = getenv('WECHAT_PAY_MCHID') ?: '';
            $this->apiKey = getenv('WECHAT_PAY_API_KEY') ?: '';
            $this->notifyUrl = getenv('WECHAT_PAY_NOTIFY_URL') ?: '';
            $this->certPath = getenv('WECHAT_PAY_CERT_PATH') ?: '';
            $this->keyPath = getenv('WECHAT_PAY_KEY_PATH') ?: '';
        }
    }
    
    /**
     * Native支付 - 下单
     * 
     * @param string $outTradeNo 商户订单号
     * @param int $totalAmount 订单总金额（单位：分）
     * @param string $description 商品描述
     * @param array $extra 额外参数（attach, time_expire等）
     * @return array 返回结果，包含code_url（二维码链接）
     * @throws Exception
     */
    public function nativePay(string $outTradeNo, int $totalAmount, string $description, array $extra = []): array
    {
        $url = '/v3/pay/transactions/native';
        
        $params = [
            'appid' => $this->appId,
            'mchid' => $this->mchId,
            'description' => $description,
            'out_trade_no' => $outTradeNo,
            'notify_url' => $this->notifyUrl,
            'amount' => [
                'total' => $totalAmount,
                'currency' => 'CNY'
            ]
        ];
        
        // 可选参数
        if (!empty($extra['attach'])) {
            $params['attach'] = $extra['attach'];
        }
        
        if (!empty($extra['time_expire'])) {
            $params['time_expire'] = $extra['time_expire'];
        }
        
        if (!empty($extra['goods_tag'])) {
            $params['goods_tag'] = $extra['goods_tag'];
        }
        
        if (!empty($extra['support_fapiao'])) {
            $params['support_fapiao'] = $extra['support_fapiao'];
        }
        
        if (!empty($extra['detail'])) {
            $params['detail'] = $extra['detail'];
        }
        
        if (!empty($extra['scene_info'])) {
            $params['scene_info'] = $extra['scene_info'];
        }
        
        return $this->request('POST', $url, $params);
    }
    
    /**
     * 查询订单
     * 
     * @param string $outTradeNo 商户订单号
     * @return array
     * @throws Exception
     */
    public function queryOrder(string $outTradeNo): array
    {
        $url = "/v3/pay/transactions/out-trade-no/{$outTradeNo}?mchid={$this->mchId}";
        return $this->request('GET', $url);
    }
    
    /**
     * 关闭订单
     * 
     * @param string $outTradeNo 商户订单号
     * @return array
     * @throws Exception
     */
    public function closeOrder(string $outTradeNo): array
    {
        $url = "/v3/pay/transactions/out-trade-no/{$outTradeNo}/close";
        return $this->request('POST', $url, ['mchid' => $this->mchId]);
    }
    
    /**
     * 申请退款
     * 
     * @param string $outRefundNo 商户退款单号
     * @param string $outTradeNo 商户订单号
     * @param int $refundAmount 退款金额（单位：分）
     * @param int $totalAmount 订单总金额（单位：分）
     * @param string $reason 退款原因
     * @return array
     * @throws Exception
     */
    public function refund(string $outRefundNo, string $outTradeNo, int $refundAmount, int $totalAmount, string $reason = ''): array
    {
        $url = '/v3/refund/domestic/refunds';
        
        $params = [
            'out_refund_no' => $outRefundNo,
            'out_trade_no' => $outTradeNo,
            'amount' => [
                'refund' => $refundAmount,
                'total' => $totalAmount,
                'currency' => 'CNY'
            ]
        ];
        
        if (!empty($reason)) {
            $params['reason'] = $reason;
        }
        
        return $this->request('POST', $url, $params);
    }
    
    /**
     * 查询退款
     * 
     * @param string $outRefundNo 商户退款单号
     * @return array
     * @throws Exception
     */
    public function queryRefund(string $outRefundNo): array
    {
        $url = "/v3/refund/domestic/refunds/{$outRefundNo}";
        return $this->request('GET', $url);
    }

    /**
     * 从 ThinkPHP 等环境中读取请求头（键名大小写不统一）
     */
    protected function getHeaderInsensitive(array $headers, string $name): string
    {
        $target = strtolower($name);
        foreach ($headers as $k => $v) {
            if (strtolower((string)$k) === $target) {
                return is_array($v) ? (string)($v[0] ?? '') : (string)$v;
            }
        }
        return '';
    }
    
    /**
     * 解密回调数据
     * 
     * @param array $resource 加密数据
     * @return array
     * @throws Exception
     */
    public function decryptNotify(array $resource): array
    {
        $algorithm = 'AEAD_AES_256_GCM';
        $ciphertext = $resource['ciphertext'] ?? '';
        $associatedData = $resource['associated_data'] ?? '';
        $nonce = $resource['nonce'] ?? '';
        
        if ($algorithm !== 'AEAD_AES_256_GCM') {
            throw new Exception('不支持的加密算法');
        }
        
        // 使用APIv3密钥解密
        $key = $this->apiKey;
        
        // Base64解码密文
        $ciphertext = base64_decode($ciphertext);
        if ($ciphertext === false) {
            throw new Exception('密文解码失败');
        }
        
        if (strlen($ciphertext) <= self::AUTH_TAG_LENGTH_BYTE) {
            throw new Exception('密文长度不足');
        }
        
        // ext-sodium (default installed on >= PHP 7.2)
        if (function_exists('sodium_crypto_aead_aes256gcm_is_available') && sodium_crypto_aead_aes256gcm_is_available()) {
            $plaintext = sodium_crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonce, $key);
        } 
        // ext-libsodium (need install libsodium-php 1.x via pecl)
        else if (function_exists('Sodium\crypto_aead_aes256gcm_is_available') && Sodium\crypto_aead_aes256gcm_is_available()) {
            $plaintext = Sodium\crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonce, $key);
        }
        // openssl (PHP >= 7.1 support AEAD)
        else if (PHP_VERSION_ID >= 70100 && in_array('aes-256-gcm', openssl_get_cipher_methods())) {
            $ctext = substr($ciphertext, 0, -self::AUTH_TAG_LENGTH_BYTE);
            $authTag = substr($ciphertext, -self::AUTH_TAG_LENGTH_BYTE);
            $plaintext = openssl_decrypt(
                $ctext, 
                'aes-256-gcm', 
                $key, 
                OPENSSL_RAW_DATA, 
                $nonce, 
                $authTag, 
                $associatedData
            );
        } else {
            throw new Exception('AEAD_AES_256_GCM需要PHP 7.1以上或者安装libsodium-php');
        }
        
        if ($plaintext === false) {
            throw new Exception('解密失败');
        }
        
        return json_decode($plaintext, true) ?? [];
    }
    
    /**
     * 获取平台证书
     * 
     * @param string $serialNo 证书序列号
     * @return string 公钥
     * @throws Exception
     */
    protected function getPlatformCertificate(string $serialNo): string
    {
        // 实际项目中应该缓存平台证书，并定期更新
        // 这里调用微信支付接口获取平台证书列表
        $url = '/v3/certificates';
        $response = $this->request('GET', $url);
        
        if (empty($response['data'])) {
            throw new Exception('获取平台证书失败');
        }
        
        foreach ($response['data'] as $cert) {
            if ($cert['serial_no'] === $serialNo) {
                // 解密证书
                return $this->decryptCertificate($cert['encrypt_certificate']);
            }
        }
        
        throw new Exception('未找到对应的平台证书');
    }
    
    /**
     * 解密平台证书
     * 
     * @param array $encryptCertificate 加密的证书信息
     * @return string 解密后的证书内容
     * @throws Exception
     */
    protected function decryptCertificate(array $encryptCertificate): string
    {
        $algorithm = $encryptCertificate['algorithm'] ?? 'AEAD_AES_256_GCM';
        $ciphertext = $encryptCertificate['ciphertext'] ?? '';
        $associatedData = $encryptCertificate['associated_data'] ?? '';
        $nonce = $encryptCertificate['nonce'] ?? '';
        
        if ($algorithm !== 'AEAD_AES_256_GCM') {
            throw new Exception('不支持的加密算法');
        }
        
        $key = $this->apiKey;
        $ciphertext = base64_decode($ciphertext);
        if ($ciphertext === false) {
            throw new Exception('证书密文解码失败');
        }
        
        $tag = substr($ciphertext, -16);
        $encrypted = substr($ciphertext, 0, -16);
        
        $plaintext = openssl_decrypt(
            $encrypted,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            $associatedData
        );
        
        if ($plaintext === false) {
            throw new Exception('证书解密失败');
        }
        
        return $plaintext;
    }
    
    /**
     * 验证签名
     * 
     * @param string $message 待验证的消息
     * @param string $signature Base64编码的签名
     * @param string $publicKey 公钥
     * @return bool
     */
    protected function verifySignature(string $message, string $signature, string $publicKey): bool
    {
        $signature = base64_decode($signature);
        if ($signature === false) {
            return false;
        }
        
        return openssl_verify($message, $signature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }
    
    /**
     * 发送HTTP请求
     * 
     * @param string $method 请求方法
     * @param string $url 请求URL（相对路径）
     * @param array $data 请求数据
     * @return array
     * @throws Exception
     */
    protected function request(string $method, string $url, array $data = []): array
    {
        $fullUrl = $this->baseUrl . $url;
        $timestamp = time();
        $nonce = $this->generateNonce();
        $body = empty($data) ? '' : json_encode($data, JSON_UNESCAPED_UNICODE);
        
        // 生成签名
        $signature = $this->generateSignature($method, $url, $timestamp, $nonce, $body);
        
        // 获取证书序列号
        $serialNo = $this->getCertificateSerialNo();
        
        // 构造Authorization头
        $auth = sprintf(
            'WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",signature="%s",timestamp="%d",serial_no="%s"',
            $this->mchId,
            $nonce,
            $signature,
            $timestamp,
            $serialNo
        );
        
        $headers = [
            'Authorization: ' . $auth,
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("请求失败：{$error}");
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMsg = $result['message'] ?? $result['detail'] ?? "HTTP错误：{$httpCode}";
            throw new Exception($errorMsg);
        }
        
        return $result ?? [];
    }
    
    /**
     * 生成签名
     * 
     * APIv3签名规则：
     * HTTP请求方法\n
     * 请求URL\n
     * 请求时间戳\n
     * 请求随机串\n
     * 请求报文主体\n
     * 
     * @param string $method 请求方法
     * @param string $url 请求URL（相对路径）
     * @param int $timestamp 时间戳
     * @param string $nonce 随机串
     * @param string $body 请求体
     * @return string Base64编码的签名
     * @throws Exception
     */
    protected function generateSignature(string $method, string $url, int $timestamp, string $nonce, string $body): string
    {
        // 构造签名串
        $message = "{$method}\n{$url}\n{$timestamp}\n{$nonce}\n{$body}\n";
        
        // 读取商户私钥
        $privateKey = $this->getPrivateKey();
        
        // 使用SHA256withRSA签名
        $signature = '';
        if (!openssl_sign($message, $signature, $privateKey, 'sha256WithRSAEncryption')) {
            throw new Exception('签名生成失败');
        }
        
        return base64_encode($signature);
    }
    
    /**
     * 获取商户私钥
     * 
     * @return string
     * @throws Exception
     */
    protected function getPrivateKey(): string
    {
        if (empty($this->keyPath)) {
            throw new Exception('未配置商户API证书私钥路径');
        }
        
        // 处理相对路径
        // $keyPath = $this->resolvePath($this->keyPath);
        $keyPath = $this->keyPath;
        
        if (!file_exists($keyPath)) {
            throw new Exception('商户API证书私钥文件不存在：' . $keyPath);
        }
        
        $privateKey = file_get_contents($keyPath);
        if ($privateKey === false) {
            throw new Exception('读取商户API证书私钥失败');
        }
        
        return $privateKey;
    }
    
    /**
     * 获取证书序列号
     * 
     * @return string
     * @throws Exception
     */
    protected function getCertificateSerialNo(): string
    {
        if (empty($this->certPath)) {
            throw new Exception('未配置商户API证书路径');
        }
        
        // 处理相对路径
        // $certPath = $this->resolvePath($this->certPath);
        $certPath = $this->certPath;
        
        if (!file_exists($certPath)) {
            throw new Exception('商户API证书文件不存在：' . $certPath);
        }
        
        $cert = file_get_contents($certPath);
        if ($cert === false) {
            throw new Exception('读取商户API证书失败');
        }
        
        // 解析证书获取序列号
        $certData = openssl_x509_read($cert);
        if (!$certData) {
            throw new Exception('解析商户API证书失败');
        }
        
        $certInfo = openssl_x509_parse($certData);
        if (empty($certInfo['serialNumber'])) {
            throw new Exception('获取证书序列号失败');
        }
        
        // 将序列号转换为16进制字符串
        $serialNo = $certInfo['serialNumber'];
        if (is_numeric($serialNo)) {
            $serialNo = strtoupper(dechex((int)$serialNo));
        }
        
        // 移除可能的0x前缀
        $serialNo = ltrim($serialNo, '0x');
        
        return $serialNo;
    }
    
    /**
     * 生成随机字符串
     * 
     * @param int $length 长度
     * @return string
     */
    protected function generateNonce(int $length = 32): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $nonce = '';
        for ($i = 0; $i < $length; $i++) {
            $nonce .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $nonce;
    }
    
    /**
     * 生成订单号
     * 
     * @return string
     */
    public function generateOrderNo(): string
    {
        return date('YmdHis') . substr(microtime(), 2, 6) . random_int(1000, 9999);
    }
    
    /**
     * 解析路径，处理相对路径
     * 
     * @param string $path
     * @return string
     */
    protected function resolvePath(string $path): string
    {
        // 如果是绝对路径，直接返回
        if (strpos($path, ':') !== false || strpos($path, '\\') === 0 || strpos($path, '/') === 0) {
            return $path;
        }
        
        // 相对路径，基于项目根目录
        $rootPath = dirname(dirname(__DIR__));
        return $rootPath . DIRECTORY_SEPARATOR . $path;
    }
}
