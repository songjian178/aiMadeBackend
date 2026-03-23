<?php

namespace app\middleware;

use think\Request;
use think\Response;

class Cors
{
    /**
     * 处理跨域请求
     * @param Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        // 允许所有域名访问
        $origin = $request->header('Origin', '*');

        // 构建响应头
        $headers = [
            'Access-Control-Allow-Origin'      => $origin,
            'Access-Control-Allow-Methods'     => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers'     => 'Content-Type, Authorization, X-Requested-With, token',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age'           => '86400',
        ];

        // 如果是OPTIONS预检请求，直接返回200
        if ($request->method() === 'OPTIONS') {
            $response = response('')
                ->code(200)
                ->header($headers);
            
            return $response;
        }
        
        // 处理正常请求
        $response = $next($request);
        
        // 添加响应头
        // foreach ($headers as $key => $value) {
        //     $response->header($key, $value);
        // }
        $response->header($headers);
        
        return $response;
    }
}
