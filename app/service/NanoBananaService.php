<?php

namespace app\service;

use think\Exception;

class NanoBananaService
{
    protected $httpClient;
    protected $apiKey;
    protected $ossId;
    protected $baseUrl = 'https://grsai.dakka.com.cn';
    
    public function __construct()
    {
        $this->baseUrl = env('NANO_BANANA_BASE_URL', $this->baseUrl);
        $this->apiKey = env('NANO_BANANA_API_KEY', '');
        $this->ossId = env('NANO_BANANA_OSS_ID', '');

        $this->httpClient = new HttpClientService($this->baseUrl, [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
            'oss-id' => $this->ossId
        ], 120);
    }
    
    /**
     * 生成图片
     * @param string $prompt 提示词
     * @param string $model 模型名称
     * @param string $aspectRatio 宽高比
     * @param string $imageSize 图片尺寸
     * @param bool $shotProgress 是否显示进度
     * @return array
     * @throws Exception
     */
    public function generateImage(
        string $prompt,
        string $aspectRatio = '3:4',
        string $imageSize = '2K',
        string $model = 'nano-banana',
        bool $shotProgress = false
    ): array {

        $data = [
            'model' => $model,
            'prompt' => $prompt,
            'aspectRatio' => $aspectRatio,
            'imageSize' => $imageSize,
            'shotProgress' => $shotProgress,
            'webHook' => "-1"
        ];
        return $this->httpClient->post('/v1/draw/nano-banana', $data);
    }
    

    
    /**
     * 设置自定义API密钥
     * @param string $apiKey
     * @return self
     */
    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;
        $this->httpClient->addHeader('Authorization', 'Bearer ' . $apiKey);
        return $this;
    }
    
    /**
     * 设置自定义OSS ID
     * @param string $ossId
     * @return self
     */
    public function setOssId(string $ossId): self
    {
        $this->ossId = $ossId;
        $this->httpClient->addHeader('oss-id', $ossId);
        return $this;
    }
    
    /**
     * 查询图片生成结果
     * @param string $taskId 任务ID
     * @return array
     * @throws Exception
     */
    public function getImageResult(string $taskId): array
    {
        $data = [
            'id' => $taskId
        ];
        
        return $this->httpClient->post('/v1/draw/result', $data);
    }
    
    /**
     * 设置自定义基础URL
     * @param string $baseUrl
     * @return self
     */
    public function setBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = $baseUrl;
        $this->httpClient->setBaseUrl($baseUrl);
        return $this;
    }
}
