# 爱制平台 API 文档

## 接口新增记录

- 2026-03-23：新增用户模块接口
- 2026-03-24：新增实体模块接口（获取实体分类列表）
- 2026-03-24：新增地址模块接口（地址增删改查）
- 2026-03-24：更新地址新增接口（单用户地址上限5条）
- 2026-03-24：新增订单模块接口（生成订单二维码）
- 2026-03-24：新增订单模块接口（我的订单列表）
- 2026-03-24：更新我的订单列表接口（增加权益过期时间 expire_time）
- 2026-03-24：新增订单模块接口（订单心跳检测）
- 2026-03-24：新增实体模块接口（获取实体分类列表-含用户可使用次数）

## 用户模块

### 1. 获取邮箱验证码

**请求地址**：`/user/get-email-code`
**请求方式**：POST
**是否需要 token**：否
**请求参数**：

| 参数名 | 类型 | 必填 | 描述 |
|-------|------|------|------|
| email | string | 是 | 邮箱地址 |

**返回示例**：

```json
// 成功
{
  "code": 200,
  "message": "验证码已发送，请注意查收",
  "data": null
}

// 失败
{
  "code": 400,
  "message": "邮箱格式不正确",
  "data": null
}

{
  "code": 400,
  "message": "该邮箱已注册",
  "data": null
}

{
  "code": 400,
  "message": "验证码发送失败，请稍后重试",
  "data": null
}
```

### 2. 用户注册

**请求地址**：`/user/register`
**请求方式**：POST
**是否需要 token**：否
**请求参数**：

| 参数名 | 类型 | 必填 | 描述 |
|-------|------|------|------|
| email | string | 是 | 邮箱地址 |
| code | string | 是 | 邮箱验证码 |
| password | string | 是 | 密码（至少6位） |
| confirm_password | string | 是 | 确认密码 |
| username | string | 否 | 用户名（可选，默认自动生成） |
| nickname | string | 否 | 昵称（可选，默认自动生成） |

**返回示例**：

```json
// 成功
{
  "code": 200,
  "message": "注册成功",
  "data": null
}

// 失败
{
  "code": 400,
  "message": "参数不完整",
  "data": null
}

{
  "code": 400,
  "message": "两次输入的密码不一致",
  "data": null
}

{
  "code": 400,
  "message": "密码长度不能少于6位",
  "data": null
}

{
  "code": 400,
  "message": "验证码无效或已过期",
  "data": null
}

{
  "code": 400,
  "message": "该邮箱已注册",
  "data": null
}

{
  "code": 400,
  "message": "注册失败，请稍后重试",
  "data": null
}
```

### 3. 用户登录

**请求地址**：`/user/login`
**请求方式**：POST
**是否需要 token**：否
**请求参数**：

| 参数名 | 类型 | 必填 | 描述 |
|-------|------|------|------|
| email | string | 是 | 邮箱地址 |
| password | string | 是 | 密码 |

**返回示例**：

```json
// 成功
{
  "code": 200,
  "message": "登录成功",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": {
      "id": 1,
      "email": "user@example.com",
      "username": "user_1234",
      "nickname": "用户1234",
      "avatar": null,
      "role": 0
    }
  }
}

// 失败
{
  "code": 400,
  "message": "邮箱和密码不能为空",
  "data": null
}

{
  "code": 400,
  "message": "用户不存在或已被禁用",
  "data": null
}

{
  "code": 400,
  "message": "密码错误",
  "data": null
}
```

### 4. 修改密码

**请求地址**：`/user/change-password`
**请求方式**：POST
**是否需要 token**：是
**请求头**：
- Authorization: Bearer {token}

**请求参数**：

| 参数名 | 类型 | 必填 | 描述 |
|-------|------|------|------|
| old_password | string | 是 | 旧密码 |
| new_password | string | 是 | 新密码（至少6位） |
| confirm_password | string | 是 | 确认新密码 |

**返回示例**：

```json
// 成功
{
  "code": 200,
  "message": "密码修改成功",
  "data": null
}

// 失败
{
  "code": 401,
  "message": "请先登录",
  "data": null
}

{
  "code": 400,
  "message": "参数不完整",
  "data": null
}

{
  "code": 400,
  "message": "两次输入的新密码不一致",
  "data": null
}

{
  "code": 400,
  "message": "新密码长度不能少于6位",
  "data": null
}

{
  "code": 400,
  "message": "旧密码错误",
  "data": null
}

{
  "code": 400,
  "message": "密码修改失败，请稍后重试",
  "data": null
}
```

### 5. 禁用/启用用户

**请求地址**：`/user/disable`
**请求方式**：POST
**是否需要 token**：是
**请求头**：
- Authorization: Bearer {token}

**请求参数**：

| 参数名 | 类型 | 必填 | 描述 |
|-------|------|------|------|
| user_id | int | 是 | 用户ID |
| status | int | 是 | 状态（0：禁用，1：启用） |

**返回示例**：

```json
// 成功
{
  "code": 200,
  "message": "用户已禁用",
  "data": null
}

{
  "code": 200,
  "message": "用户已启用",
  "data": null
}

// 失败
{
  "code": 401,
  "message": "请先登录",
  "data": null
}

{
  "code": 403,
  "message": "权限不足",
  "data": null
}

{
  "code": 400,
  "message": "参数不完整",
  "data": null
}

{
  "code": 400,
  "message": "不能操作自己的账户",
  "data": null
}

{
  "code": 400,
  "message": "操作失败，请稍后重试",
  "data": null
}
```

## 实体模块

### 1. 获取实体分类列表

**请求地址**：`/entity/category-list`
**请求方式**：GET
**是否需要 token**：否
**请求参数**：无

**返回示例**：

```json
// 成功
{
  "code": 200,
  "message": "获取实体分类成功",
  "data": [
    {
      "id": 1,
      "name": "基础款",
      "price": "99.00",
      "validity_period": 30,
      "render_count": 20,
      "description": "适合轻量体验",
      "image_url": "https://example.com/category/basic.png",
      "sort_order": 1
    }
  ]
}

// 失败
{
  "code": 500,
  "message": "系统错误",
  "data": null
}
```

### 2. 获取实体分类列表（含用户可使用次数）

**请求地址**：`/entity/category-list-with-usage`
**请求方式**：GET
**是否需要 token**：是
**请求头**：
- Authorization: Bearer {token}
**请求参数**：无

**返回示例**：

```json
// 成功
{
  "code": 200,
  "message": "获取实体分类成功",
  "data": [
    {
      "id": 1,
      "name": "基础款",
      "price": "99.00",
      "validity_period": 30,
      "render_count": 20,
      "description": "适合轻量体验",
      "image_url": "https://example.com/category/basic.png",
      "sort_order": 1,
      "user_available_count": 17
    }
  ]
}

// 失败
{
  "code": 401,
  "message": "请先登录",
  "data": null
}
```

## 地址模块

### 1. 新增地址

**请求地址**：`/address/create`
**请求方式**：POST
**是否需要 token**：是
**请求头**：
- Authorization: Bearer {token}

**请求参数**：

| 参数名 | 类型 | 必填 | 描述 |
|-------|------|------|------|
| recipient | string | 是 | 收件人 |
| phone | string | 是 | 手机号 |
| province | string | 是 | 省份 |
| city | string | 是 | 城市 |
| district | string | 是 | 区县 |
| address | string | 是 | 详细地址 |
| zip_code | string | 否 | 邮政编码 |
| is_default | int | 否 | 是否默认地址（1：是，0：否，默认0） |

**返回示例**：

```json
// 成功
{
  "code": 200,
  "message": "地址新增成功",
  "data": {
    "id": 1
  }
}

// 失败
{
  "code": 401,
  "message": "请先登录",
  "data": null
}

{
  "code": 400,
  "message": "参数不完整",
  "data": null
}

{
  "code": 400,
  "message": "最多只能添加5个地址",
  "data": null
}

{
  "code": 400,
  "message": "地址新增失败，请稍后重试",
  "data": null
}
```

### 2. 删除地址

**请求地址**：`/address/delete`
**请求方式**：POST
**是否需要 token**：是
**请求头**：
- Authorization: Bearer {token}

**请求参数**：

| 参数名 | 类型 | 必填 | 描述 |
|-------|------|------|------|
| id | int | 是 | 地址ID |

**返回示例**：

```json
// 成功
{
  "code": 200,
  "message": "地址删除成功",
  "data": null
}

// 失败
{
  "code": 401,
  "message": "请先登录",
  "data": null
}

{
  "code": 400,
  "message": "参数不完整",
  "data": null
}

{
  "code": 400,
  "message": "地址不存在",
  "data": null
}

{
  "code": 400,
  "message": "地址删除失败，请稍后重试",
  "data": null
}
```

### 3. 修改地址

**请求地址**：`/address/update`
**请求方式**：POST
**是否需要 token**：是
**请求头**：
- Authorization: Bearer {token}

**请求参数**：

| 参数名 | 类型 | 必填 | 描述 |
|-------|------|------|------|
| id | int | 是 | 地址ID |
| recipient | string | 否 | 收件人 |
| phone | string | 否 | 手机号 |
| province | string | 否 | 省份 |
| city | string | 否 | 城市 |
| district | string | 否 | 区县 |
| address | string | 否 | 详细地址 |
| zip_code | string | 否 | 邮政编码 |
| is_default | int | 否 | 是否默认地址（1：是，0：否） |

**返回示例**：

```json
// 成功
{
  "code": 200,
  "message": "地址修改成功",
  "data": null
}

// 失败
{
  "code": 401,
  "message": "请先登录",
  "data": null
}

{
  "code": 400,
  "message": "参数不完整",
  "data": null
}

{
  "code": 400,
  "message": "地址不存在",
  "data": null
}

{
  "code": 400,
  "message": "没有可更新的内容",
  "data": null
}

{
  "code": 400,
  "message": "地址修改失败，请稍后重试",
  "data": null
}
```

### 4. 查询所有地址

**请求地址**：`/address/list`
**请求方式**：GET
**是否需要 token**：是
**请求头**：
- Authorization: Bearer {token}

**请求参数**：无

**返回示例**：

```json
// 成功
{
  "code": 200,
  "message": "获取地址列表成功",
  "data": [
    {
      "id": 1,
      "recipient": "张三",
      "phone": "13800000000",
      "province": "广东省",
      "city": "深圳市",
      "district": "南山区",
      "address": "科技园 1 号",
      "zip_code": "518000",
      "is_default": 1,
      "created_at": "2026-03-24 10:00:00",
      "updated_at": "2026-03-24 10:00:00"
    }
  ]
}

// 失败
{
  "code": 401,
  "message": "请先登录",
  "data": null
}
```

## 订单模块

### 1. 生成订单二维码

**请求地址**：`/order/create-pay-qrcode`
**请求方式**：POST
**是否需要 token**：是
**请求头**：
- Authorization: Bearer {token}

**请求参数**：

| 参数名 | 类型 | 必填 | 描述 |
|-------|------|------|------|
| category_id | int | 是 | 实体分类ID |

**返回示例**：

```json
// 成功
{
  "code": 200,
  "message": "订单二维码生成成功",
  "data": {
    "order_id": 1,
    "order_no": "AM2026032412304599",
    "payment_method": "WX",
    "qr_code_url": "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=WeChatPay_Fake_Link"
  }
}

// 失败
{
  "code": 401,
  "message": "请先登录",
  "data": null
}

{
  "code": 400,
  "message": "参数不完整",
  "data": null
}

{
  "code": 400,
  "message": "实体分类不存在或不可用",
  "data": null
}

{
  "code": 400,
  "message": "订单创建失败，请稍后重试",
  "data": null
}
```

### 2. 订单心跳检测

**请求地址**：`/order/heartbeat`
**请求方式**：POST
**是否需要 token**：是
**请求头**：
- Authorization: Bearer {token}

**请求参数**：

| 参数名 | 类型 | 必填 | 描述 |
|-------|------|------|------|
| order_no | string | 是 | 订单编号 |

**返回示例**：

```json
// 成功（已支付）
{
  "code": 200,
  "message": "心跳检测成功",
  "data": {
    "order_no": "AM2026032412304599",
    "payment_status": 1,
    "result": "支付成功"
  }
}

// 成功（未支付/支付失败）
{
  "code": 200,
  "message": "心跳检测成功",
  "data": {
    "order_no": "AM2026032412304599",
    "payment_status": 0,
    "result": "支付失败"
  }
}

// 失败
{
  "code": 401,
  "message": "请先登录",
  "data": null
}

{
  "code": 400,
  "message": "参数不完整",
  "data": null
}

{
  "code": 400,
  "message": "订单不存在",
  "data": null
}
```

### 3. 我的订单列表

**请求地址**：`/order/my-list`  
**请求方式**：GET  
**是否需要 token**：是  
**请求头**：
- Authorization: Bearer {token}

**请求参数**：无  

**说明**：仅返回当前用户 `payment_status = 1`（已支付）的订单；数据由 `aimade_entity_order` 与 `aimade_user_purchased_entity` 内连表得到，并关联品类表展示名称与套餐渲染次数。购买时间取购买权益记录创建时间；`expire_time` 为 `aimade_user_purchased_entity.expire_time`（权益使用过期时间）。`order_status_name` 与库中枚举对应：0 待使用、1 生成中、2 下单、3 打样、4 生产、5 发货。

**返回示例**：

```json
// 成功
{
  "code": 200,
  "message": "获取订单列表成功",
  "data": [
    {
      "order_id": 1,
      "order_no": "AM2026032412304599",
      "category_name": "基础款",
      "initial_render_count": 20,
      "used_render_count": 3,
      "remaining_render_count": 17,
      "purchase_time": "2026-03-24 12:35:00",
      "expire_time": "2026-04-23 12:35:00",
      "order_status": 0,
      "order_status_name": "待使用",
      "total_amount": "99.00"
    }
  ]
}

// 失败
{
  "code": 401,
  "message": "请先登录",
  "data": null
}
```

### 4. 支付回调（测试伪回调）

**请求地址**：`/order/pay-callback`    
**请求方式**：POST  
**是否需要 token**：否

> 说明：当前第三方支付平台尚未接入，该接口先使用服务端接收参数模拟回调，再执行既定支付成功逻辑。

**请求参数**：

| 参数名 | 类型 | 必填 | 描述 |
|-------|------|------|------|
| order_no | string | 是 | 订单编号 |
| amount | string | 是 | 回调支付金额（需与订单金额一致） |
| payment_transaction_id | string | 否 | 第三方支付交易号 |
<!-- | payment_method | string | 否 | 支付方式，默认 `WX` |
| payment_status | int | 否 | 支付状态，当前仅支持 `1`（支付成功） | -->
| payment_time | string | 否 | 支付时间（格式：`Y-m-d H:i:s`，默认当前时间） |

**返回示例**：

```json
// 成功
{
  "code": 200,
  "message": "支付回调处理成功",
  "data": {
    "order_no": "AM2026032412304599",
    "payment_status": 1
  }
}

// 幂等成功（已处理过）
{
  "code": 200,
  "message": "订单已处理",
  "data": {
    "order_no": "AM2026032412304599"
  }
}

// 失败
{
  "code": 400,
  "message": "回调参数不完整",
  "data": null
}

{
  "code": 400,
  "message": "当前仅支持支付成功回调",
  "data": null
}

{
  "code": 400,
  "message": "订单不存在",
  "data": null
}

{
  "code": 400,
  "message": "回调金额与订单金额不一致",
  "data": null
}

{
  "code": 400,
  "message": "支付回调处理失败，请稍后重试",
  "data": null
}
```
