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
3. 控制器中保留业务逻辑处理，避免重复写“是否登录”的判断代码。

## 微信支付功能使用说明

本项目已集成微信支付功能，支持扫码支付方式。

### 配置说明

在 `.env` 文件中配置微信支付相关参数：

```env
# 微信支付配置
WECHAT_PAY_APPID=your-appid
WECHAT_PAY_MCHID=your-mch-id
WECHAT_PAY_API_KEY=your-api-key
WECHAT_PAY_NOTIFY_URL=http://your-domain/pay/notify
```

### 核心服务类

- `app/service/WechatPayService.php` - 微信支付服务类，提供生成支付二维码和处理回调的功能

### 支付相关接口

#### 生成微信支付二维码
- **接口地址**：`POST /pay/create-qr-code`
- **请求参数**：
  - `out_trade_no`：商户订单号（必填）
  - `total_fee`：订单金额（元，必填）
  - `body`：商品描述（必填）
  - `attach`：附加数据（选填）
- **返回结果**：
  ```json
  {
    "code": 200,
    "message": "生成支付二维码成功",
    "data": {
      "code_url": "weixin://wxpay/bizpayurl?pr=xxxxxx",
      "out_trade_no": "订单号"
    }
  }
  ```

#### 微信支付回调
- **接口地址**：`POST /pay/notify`
- **说明**：此接口由微信服务器调用，用于通知支付结果

### 使用示例

#### 生成支付二维码

```php
// 在控制器中使用
use app\service\WechatPayService;

public function createPay()
{
    $outTradeNo = 'ORDER_' . date('YmdHis') . rand(1000, 9999);
    $totalFee = 100.00; // 100元
    $body = '测试商品';
    $attach = '测试附加数据';

    $wechatPayService = new WechatPayService();
    $result = $wechatPayService->createQrCode($outTradeNo, $totalFee * 100, $body, $attach);

    // 生成二维码图片
    // 可以使用第三方库如 endroid/qr-code 生成二维码图片

    return $this->success($result, '生成支付二维码成功');
}
```

#### 处理支付回调

微信支付回调已在 `Pay` 控制器中实现，当用户支付成功后，微信服务器会调用 `POST /pay/notify` 接口，服务端会自动处理回调并更新订单状态。

### 注意事项

1. **回调地址配置**：
   - 回调地址必须是外网可访问的域名
   - 回调地址不能带参数
   - 回调地址必须使用 `http://` 或 `https://` 协议

2. **安全验证**：
   - 服务端会验证微信回调的签名，确保数据的真实性
   - 处理回调时需要验证订单状态，避免重复处理

3. **订单处理**：
   - 在回调处理中，需要更新订单状态为已支付
   - 建议使用事务处理，确保订单状态更新和库存扣减的原子性

4. **日志记录**：
   - 建议记录支付相关的日志，便于排查问题
   - 特别是回调处理的日志，便于追踪支付状态

### 所需材料

要使用微信支付功能，您需要准备以下材料：

1. **微信支付商户号**：在 [微信支付商户平台](https://pay.weixin.qq.com/) 注册并获取
2. **AppID**：可以使用公众号 AppID 或小程序 AppID
3. **API 密钥**：在微信支付商户平台设置，用于签名验证
4. **回调域名**：需要在微信支付商户平台配置，确保回调地址可访问

### 测试环境

微信支付提供沙箱环境，用于测试支付功能：

1. **沙箱环境地址**：`https://api.mch.weixin.qq.com/sandboxnew/pay/unifiedorder`
2. **沙箱密钥**：需要通过接口获取，具体参考微信支付文档

在测试环境中，您可以使用测试账号进行支付测试，无需真实资金交易。
