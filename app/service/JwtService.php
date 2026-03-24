<?php
declare (strict_types = 1);

namespace app\service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * JWT 服务类
 */
class JwtService
{
    /**
     * 密钥
     * @var string
     */
    protected $secretKey;
    
    /**
     * 签发者
     * @var string
     */
    protected $issuer;
    
    /**
     * 过期时间（秒）
     * @var int
     */
    protected $expireTime;
    
    /**
     * 构造方法
     */
    public function __construct()
    {
        // 从配置文件获取密钥，默认使用框架密钥
        $this->secretKey = config('app.app_key') ?: '5exzbTDScQRkks3RMVcZaSCu4b3uFcjm';
        $this->issuer = config('app.app_name') ?: 'aiMadeBackend';
        $this->expireTime = 86400; // 默认1小时
    }
    
    /**
     * 生成 token
     * @param array $payload 载荷数据
     * @param int $expire 过期时间（秒）
     * @return string
     */
    public function generateToken(array $payload, int $expire = null): string
    {
        $expire = $expire ?? $this->expireTime;
        
        $tokenPayload = [
            'iss' => $this->issuer, // 签发者
            'iat' => time(), // 签发时间
            'exp' => time() + $expire, // 过期时间
            'data' => $payload // 自定义数据
        ];
        
        return JWT::encode($tokenPayload, $this->secretKey, 'HS256');
    }
    
    /**
     * 验证 token
     * @param string $token
     * @return array|false
     */
    public function verifyToken(string $token): array|false
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            return (array) $decoded;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 获取 token 中的数据
     * @param string $token
     * @return array|false
     */
    public function getTokenData(string $token): array|false
    {
        $decoded = $this->verifyToken($token);
        if ($decoded && isset($decoded['data'])) {
            return (array) $decoded['data'];
        }
        return false;
    }
    
    /**
     * 刷新 token
     * @param string $token
     * @param int $expire 新的过期时间（秒）
     * @return string|false
     */
    public function refreshToken(string $token, int $expire = null): string|false
    {
        $data = $this->getTokenData($token);
        if ($data) {
            return $this->generateToken($data, $expire);
        }
        return false;
    }
}
