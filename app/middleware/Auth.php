<?php

namespace app\middleware;

use app\service\JwtService;

class Auth
{
    /**
     * 鉴权中间件：校验请求中的 JWT token
     * @param \think\Request $request
     * @param \Closure $next
     * @return \think\Response
     */
    public function handle($request, \Closure $next)
    {
        $authorization = $request->header('Authorization');
        $token = '';

        if ($authorization && strpos($authorization, 'Bearer ') === 0) {
            $token = substr($authorization, 7);
        } else {
            $token = (string)$request->param('token', '');
        }

        if ($token === '') {
            return json([
                'code' => 401,
                'message' => '请先登录',
                'data' => null
            ]);
        }

        $jwtService = new JwtService();
        $decoded = $jwtService->verifyToken($token);
        if (!$decoded || !isset($decoded['data'])) {
            return json([
                'code' => 401,
                'message' => '请先登录',
                'data' => null
            ]);
        }

        return $next($request);
    }
}
