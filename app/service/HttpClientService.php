<?php

namespace app\service;

use think\Exception;

class HttpClientService
{
    protected $baseUrl;
    protected $headers = [];
    protected $timeout = 30;
    
    public function __construct(string $baseUrl = '', array $headers = [], int $timeout = 30)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->headers = $headers;
        $this->timeout = $timeout;
    }
    
    public function setBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        return $this;
    }
    
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }
    
    public function addHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }
    
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }
    
    public function post(string $endpoint, array $data = [], array $extraHeaders = []): array
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        
        $headers = array_merge($this->headers, $extraHeaders);
        $headerLines = [];
        foreach ($headers as $key => $value) {
            $headerLines[] = "{$key}: {$value}";
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerLines);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("请求失败：{$error}");
        }
        
        $result = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMsg = $result['message'] ?? $result['error'] ?? "HTTP错误：{$httpCode}";
            throw new Exception($errorMsg);
        }
        
        return $result ?? [];
    }
    
    public function get(string $endpoint, array $params = [], array $extraHeaders = []): array
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $headers = array_merge($this->headers, $extraHeaders);
        $headerLines = [];
        foreach ($headers as $key => $value) {
            $headerLines[] = "{$key}: {$value}";
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerLines);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("请求失败：{$error}");
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMsg = $result['message'] ?? $result['error'] ?? "HTTP错误：{$httpCode}";
            throw new Exception($errorMsg);
        }
        
        return $result ?? [];
    }
}
