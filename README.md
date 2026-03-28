![](https://www.thinkphp.cn/uploads/images/20230630/300c856765af4d8ae758c503185f8739.png)

ThinkPHP 8
===============

## 特性

* 基于PHP`8.0+`重构
* 升级`PSR`依赖
* 依赖`think-orm`3.0+版本
* 全新的`think-dumper`服务，支持远程调试
* 支持`6.0`/`6.1`无缝升级

> ThinkPHP8的运行环境要求PHP8.0+

现在开始，你可以使用官方提供的[ThinkChat](https://chat.topthink.com/)，让你在学习ThinkPHP的旅途中享受私人AI助理服务！

![](https://www.topthink.com/uploads/assistant/20230630/4d1a3f0ad2958b49bb8189b7ef824cb0.png)

ThinkPHP生态服务由[顶想云](https://www.topthink.com)（TOPThink Cloud）提供，为生态提供专业的开发者服务和价值之选。

## 文档

[完全开发手册](https://doc.thinkphp.cn)


## 赞助

全新的[赞助计划](https://www.thinkphp.cn/sponsor)可以让你通过我们的网站、手册、欢迎页及GIT仓库获得巨大曝光，同时提升企业的品牌声誉，也更好保障ThinkPHP的可持续发展。

[![](https://www.thinkphp.cn/sponsor/special.svg)](https://www.thinkphp.cn/sponsor/special)

[![](https://www.thinkphp.cn/sponsor.svg)](https://www.thinkphp.cn/sponsor)

## 安装

~~~
composer create-project topthink/think tp
~~~

启动服务

~~~
cd tp
php think run
~~~

然后就可以在浏览器中访问

~~~
http://localhost:8000
~~~

如果需要更新框架使用
~~~
composer update topthink/framework
~~~

## 命名规范

`ThinkPHP`遵循PSR-2命名规范和PSR-4自动加载规范。

## 参与开发

直接提交PR或者Issue即可

## 版权信息

ThinkPHP遵循Apache2开源协议发布，并提供免费使用。

本项目包含的第三方源码和二进制文件之版权信息另行标注。

版权所有Copyright © 2006-2024 by ThinkPHP (http://thinkphp.cn) All rights reserved。

ThinkPHP® 商标和著作权所有者为上海顶想信息科技有限公司。

更多细节参阅 [LICENSE.txt](LICENSE.txt)

## 项目基础方法使用说明

本项目在 `app/BaseController.php` 中已预置了常用的基础方法，所有控制器继承 `BaseController` 后即可直接使用。

### JSON响应方法

#### success() - 返回成功响应
```php
// 返回成功响应（带数据）
return $this->success($data, '操作成功', 200);

// 返回成功响应（仅消息）
return $this->success(null, '创建成功');

// 返回成功响应（默认消息）
return $this->success($data);
```

#### error() - 返回失败响应
```php
// 返回失败响应（带错误信息）
return $this->error('参数错误', 400);

// 返回失败响应（带数据和状态码）
return $this->error('操作失败', 500, ['error_code' => 'OPERATION_FAILED']);

// 返回失败响应（默认消息）
return $this->error();
```

### 数组辅助方法

#### arrayToCamel() - 下划线转驼峰
```php
$data = ['user_name' => '张三', 'user_age' => 25];
$result = $this->arrayToCamel($data);
// 结果: ['userName' => '张三', 'userAge' => 25]
```

#### arrayToSnake() - 驼峰转下划线
```php
$data = ['userName' => '张三', 'userAge' => 25];
$result = $this->arrayToSnake($data);
// 结果: ['user_name' => '张三', 'user_age' => 25]
```

#### arrayFilterEmpty() - 过滤空值
```php
$data = ['name' => '张三', 'age' => '', 'email' => null, 'phone' => []];
$result = $this->arrayFilterEmpty($data);
// 结果: ['name' => '张三']
```

#### arrayGet() - 获取指定键值
```php
$data = ['name' => '张三', 'age' => 25, 'email' => 'test@example.com'];

// 获取单个键值
$name = $this->arrayGet($data, 'name', '默认值');

// 获取多个键值
$result = $this->arrayGet($data, ['name', 'age']);
// 结果: ['name' => '张三', 'age' => 25]
```

#### arrayPaginate() - 数组分页
```php
$data = range(1, 100); // 1-100的数组
$result = $this->arrayPaginate($data, 1, 10);
// 结果: 
// [
//     'data' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
//     'total' => 100,
//     'page' => 1,
//     'per_page' => 10,
//     'last_page' => 10
// ]
```

### 使用示例

```php
<?php
namespace app\controller;

use app\BaseController;

class UserController extends BaseController
{
    public function index()
    {
        $users = [
            ['user_id' => 1, 'user_name' => '张三'],
            ['user_id' => 2, 'user_name' => '李四'],
        ];
        
        // 转换为驼峰命名
        $users = $this->arrayToCamel($users);
        
        // 返回成功响应
        return $this->success($users, '获取用户列表成功');
    }
    
    public function store()
    {
        $data = $this->request->post();
        
        // 验证数据
        if (empty($data['name'])) {
            return $this->error('用户名不能为空');
        }
        
        // 过滤空值
        $data = $this->arrayFilterEmpty($data);
        
        // 业务处理...
        
        return $this->success($data, '创建用户成功');
    }
}
```

## JWT 功能使用说明

本项目已集成 JWT 功能，用于用户认证和生成访问令牌。

### 安装的依赖
- `firebase/php-jwt` - 用于生成和验证 JWT token

### 核心服务类
- `app/service/JwtService.php` - JWT 服务类，提供 token 的生成、验证、刷新等功能

### 控制器中的 JWT 方法

#### generateToken() - 生成 token
```php
// 生成 token（默认1小时过期）
$token = $this->generateToken([
    'user_id' => 1,
    'user_name' => '张三'
]);

// 生成 token（自定义过期时间，2小时）
$token = $this->generateToken([
    'user_id' => 1,
    'user_name' => '张三'
], 7200);
```

#### verifyToken() - 验证 token
```php
$token = 'your-jwt-token';
$result = $this->verifyToken($token);
if ($result) {
    // token 验证成功
    $payload = $result;
} else {
    // token 验证失败
}
```

#### getTokenData() - 获取 token 中的数据
```php
$token = 'your-jwt-token';
$data = $this->getTokenData($token);
if ($data) {
    // 获取成功
    $userId = $data['user_id'];
}
```

#### getTokenFromRequest() - 从请求中获取 token
```php
// 从 Authorization 头或请求参数中获取 token
$token = $this->getTokenFromRequest();
```

#### validateToken() - 验证请求中的 token
```php
// 验证请求中的 token
$result = $this->validateToken();
if ($result) {
    // token 验证成功
    $userData = $result['data'];
} else {
    // token 验证失败
    return $this->error('无效的 token', 401);
}
```

### JWT 服务类的使用

```php
// 在任何地方使用 JWT 服务
use app\service\JwtService;

$jwtService = new JwtService();

// 生成 token
$token = $jwtService->generateToken(['user_id' => 1]);

// 验证 token
$decoded = $jwtService->verifyToken($token);

// 获取 token 数据
$data = $jwtService->getTokenData($token);

// 刷新 token
$newToken = $jwtService->refreshToken($token);
```

### 完整的登录示例

```php
<?php
namespace app\controller;

use app\BaseController;

class AuthController extends BaseController
{
    public function login()
    {
        $username = $this->request->post('username');
        $password = $this->request->post('password');
        
        // 验证用户名和密码
        if ($username === 'admin' && $password === '123456') {
            // 生成 token
            $token = $this->generateToken([
                'user_id' => 1,
                'username' => $username,
                'role' => 'admin'
            ]);
            
            return $this->success([
                'token' => $token,
                'expire' => time() + 3600 // 1小时后过期
            ], '登录成功');
        }
        
        return $this->error('用户名或密码错误');
    }
    
    public function profile()
    {
        // 验证 token
        $tokenData = $this->validateToken();
        if (!$tokenData) {
            return $this->error('请先登录', 401);
        }
        
        // 获取用户信息
        $userData = $tokenData['data'];
        
        return $this->success($userData, '获取用户信息成功');
    }
}
```

### 配置说明

JWT 服务默认使用框架的 `app_key` 作为密钥，你可以在 `.env` 文件中设置：

```env
APP_KEY=your-secret-key-here
```

如果未设置 `APP_KEY`，将使用默认值 `your-secret-key`，建议在生产环境中设置一个安全的密钥。

## 鉴权中间件使用说明

为避免在控制器中重复编写 token 校验逻辑，项目新增了统一鉴权中间件：

- 中间件文件：`app/middleware/Auth.php`
- 别名配置：`config/middleware.php` 中的 `auth`

### 路由中引用方式

对需要登录权限的接口，在路由组上直接挂载 `auth` 中间件：

```php
Route::group('address', function () {
    Route::post('create', 'Address/create');
    Route::post('delete', 'Address/delete');
    Route::post('update', 'Address/update');
    Route::get('list', 'Address/list');
})->middleware('auth');
```

### 推荐实践

1. **公开接口**（如登录、注册、获取验证码）不挂载 `auth` 中间件。
2. **需登录接口**（如修改密码、地址管理等）统一在路由层挂载 `auth` 中间件。
3. 控制器中保留业务逻辑处理，避免重复写"是否登录"的判断代码。

## 微信支付功能使用说明

本项目已集成微信支付 APIv3 版本，支持 Native 支付（扫码支付）。

### 微信支付 APIv3 文档参考

- [Native 下单](https://pay.weixin.qq.com/doc/v3/merchant/4012791877)
- [APIv3 签名规则](https://pay.weixin.qq.com/doc/v3/merchant/4012365342)

### 需要提供的配置信息

在使用微信支付功能前，你需要准备以下配置：

#### 1. 基础配置（.env 文件）

```env
# 微信支付配置
WECHAT_PAY_APPID=你的微信APPID
WECHAT_PAY_MCHID=你的微信商户号
WECHAT_PAY_API_KEY=你的APIv3密钥
WECHAT_PAY_NOTIFY_URL=https://你的域名/pay/notify
WECHAT_PAY_CERT_PATH=/path/to/apiclient_cert.pem
WECHAT_PAY_KEY_PATH=/path/to/apiclient_key.pem
```

#### 2. 需要申请的证书和密钥

| 配置项 | 说明 | 获取方式 |
|--------|------|----------|
| `WECHAT_PAY_APPID` | 微信应用ID | 微信开放平台或微信公众平台申请 |
| `WECHAT_PAY_MCHID` | 微信商户号 | 微信支付商户平台申请 |
| `WECHAT_PAY_API_KEY` | APIv3密钥 | 微信支付商户平台 -> API安全 -> 设置APIv3密钥 |
| `WECHAT_PAY_CERT_PATH` | 商户API证书路径 | 微信支付商户平台 -> API安全 -> 申请API证书 |
| `WECHAT_PAY_KEY_PATH` | 商户API私钥路径 | 下载证书时同时获得 |

#### 3. 证书申请步骤

1. 登录 [微信支付商户平台](https://pay.weixin.qq.com/)
2. 进入【账户中心】->【API安全】
3. 申请API证书（如已申请可跳过）
4. 下载证书文件，包含：
   - `apiclient_cert.pem` - 商户API证书
   - `apiclient_key.pem` - 商户API私钥
5. 设置APIv3密钥（32位随机字符串）

### 核心服务类

- `app/service/WechatPayService.php` - 微信支付服务类

### 微信支付签名机制（APIv3）

微信支付APIv3使用 **RSA-SHA256** 签名算法，签名串格式如下：

```
HTTP请求方法\n
请求URL\n
请求时间戳\n
请求随机串\n
请求报文主体\n
```

示例：
```
POST
/v3/pay/transactions/native
1554208460
593BEC0C930BF1AFEB40B4A08C8FB242
{"appid":"wxd678efh567hg6787","mchid":"1230000109","description":"Image形象店-深圳腾大-QQ公仔","out_trade_no":"1217752501201407033233368018","notify_url":"https://www.weixin.qq.com/wxpay/pay.php","amount":{"total":100,"currency":"CNY"}}

```

签名生成步骤：
1. 构造签名串（按照上述格式）
2. 使用商户API私钥对签名串进行SHA256withRSA签名
3. 对签名结果进行Base64编码