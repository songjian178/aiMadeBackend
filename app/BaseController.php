<?php
declare (strict_types = 1);

namespace app;

use think\App;
use think\exception\ValidateException;
use think\facade\Db;
use think\Validate;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {}

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, string|array $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    /**
     * 返回成功的JSON响应
     * @access protected
     * @param  mixed  $data    返回的数据
     * @param  string $message 提示信息
     * @param  int    $code    状态码
     * @return \think\Response
     */
    protected function success($data = null, string $message = '操作成功', int $code = 200)
    {
        return json([
            'code'    => $code,
            'message' => $message,
            'data'    => $data
        ]);
    }

    /**
     * 返回失败的JSON响应
     * @access protected
     * @param  string $message 提示信息
     * @param  int    $code    状态码
     * @param  mixed  $data    返回的数据
     * @return \think\Response
     */
    protected function error(string $message = '操作失败', int $code = 400, $data = null)
    {
        return json([
            'code'    => $code,
            'message' => $message,
            'data'    => $data
        ]);
    }

    /**
     * 数组键值转换（下划线转驼峰）
     * @access protected
     * @param  array $array 原始数组
     * @return array
     */
    protected function arrayToCamel(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
            if (is_array($value)) {
                $value = $this->arrayToCamel($value);
            }
            $result[$newKey] = $value;
        }
        return $result;
    }

    /**
     * 数组键值转换（驼峰转下划线）
     * @access protected
     * @param  array $array 原始数组
     * @return array
     */
    protected function arrayToSnake(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = strtolower(preg_replace('/([A-Z])/', '_$1', $key));
            if (is_array($value)) {
                $value = $this->arrayToSnake($value);
            }
            $result[$newKey] = $value;
        }
        return $result;
    }

    /**
     * 数组过滤空值
     * @access protected
     * @param  array $array 原始数组
     * @return array
     */
    protected function arrayFilterEmpty(array $array): array
    {
        return array_filter($array, function($value) {
            return $value !== '' && $value !== null && $value !== [];
        });
    }

    /**
     * 数组获取指定键值
     * @access protected
     * @param  array        $array   原始数组
     * @param  string|array $keys    键名
     * @param  mixed        $default 默认值
     * @return mixed
     */
    protected function arrayGet(array $array, $keys, $default = null)
    {
        if (is_string($keys)) {
            return $array[$keys] ?? $default;
        }
        
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $array[$key] ?? $default;
        }
        return $result;
    }

    /**
     * 数组分页
     * @access protected
     * @param  array $array   原始数组
     * @param  int   $page    当前页码
     * @param  int   $perPage 每页数量
     * @return array
     */
    protected function arrayPaginate(array $array, int $page = 1, int $perPage = 10): array
    {
        $total = count($array);
        $offset = ($page - 1) * $perPage;
        $data = array_slice($array, $offset, $perPage);
        
        return [
            'data'  => $data,
            'total' => $total,
            'page'  => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage)
        ];
    }

    /**
     * 获取 JWT 服务实例
     * @access protected
     * @return \app\service\JwtService
     */
    protected function jwt()
    {
        return new \app\service\JwtService();
    }

    /**
     * 生成 JWT token
     * @access protected
     * @param  array $payload 载荷数据
     * @param  int   $expire  过期时间（秒）
     * @return string
     */
    protected function generateToken(array $payload, int $expire = null): string
    {
        return $this->jwt()->generateToken($payload, $expire);
    }

    /**
     * 验证 JWT token
     * @access protected
     * @param  string $token
     * @return array|false
     */
    protected function verifyToken(string $token): array|false
    {
        return $this->jwt()->verifyToken($token);
    }

    /**
     * 获取 token 中的数据
     * @access protected
     * @param  string $token
     * @return array|false
     */
    protected function getTokenData(string $token): array|false
    {
        return $this->jwt()->getTokenData($token);
    }

    /**
     * 从请求中获取 token
     * @access protected
     * @return string|false
     */
    protected function getTokenFromRequest(): string|false
    {
        $token = $this->request->header('Authorization');
        if ($token && strpos($token, 'Bearer ') === 0) {
            return substr($token, 7);
        }
        return $this->request->param('token', false);
    }

    /**
     * 验证请求中的 token
     * @access protected
     * @return array|false
     */
    protected function validateToken(): array|false
    {
        $token = $this->getTokenFromRequest();
        if (!$token) {
            return false;
        }
        return $this->verifyToken($token);
    }

    /**
     * 记录操作日志
     * @access protected
     * @param  string   $operationType    操作类型
     * @param  string   $operationContent 操作内容
     * @param  int|null $userId           用户ID
     * @param  int      $logLevel         日志等级（1:info 2:warning 3:error）
     * @return void
     */
    protected function writeLog(string $operationType, string $operationContent, ?int $userId = null, int $logLevel = 1): void
    {
        try {
            Db::name('log')->insert([
                'user_id' => $userId ?: null,
                'operation_type' => $operationType,
                'operation_ip' => $this->request->ip(),
                'user_agent' => $this->request->header('user-agent', ''),
                'operation_content' => $operationContent,
                'log_level' => $logLevel,
                'status' => 1
            ]);
        } catch (\Throwable $e) {
            // 日志写入不应影响主业务流程
        }
    }

}
