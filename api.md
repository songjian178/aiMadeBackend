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
- 2026-03-24：新增订单模块接口（生成图片资格校验）
- 2026-03-24：新增图片模块接口（查询图片生成状态）
- 2026-03-24：新增图片模块接口（获取当前分类下用户已生成图片）
- 2026-03-24：新增图片模块接口（生成图片资格校验）
- 2026-03-24：更新生成图片资格校验接口（按分类ID校验）
- 2026-03-25：新增实体模块接口（用户制作历史）
- 2026-03-25：更新生成图片接口（增加分享到社区参数）
- 2026-03-27：新增图片模块接口（获取用户分享的创意图片）
- 2026-03-28：更新订单模块「生成订单二维码」接口（整合微信 Native 下单，返回真实 code_url）
- 2026-03-28：新增创意社区模块接口（收藏社区图片、获取我的收藏列表）
- 2026-03-29：更新生成图片接口（prompt 敏感内容校验）
- 2026-03-30：新增图片模块接口（生成最终实体渲染图）
- 2026-03-30：新增订单模块接口（基于渲染图下单）
- 2026-03-30：新增订单模块接口（获取订单状态列表）
- 2026-03-30：新增实体模块接口（获取实体详情）


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
      "sort_order": 1,
      "urls": [
        "https://example.com/category/basic/banner1.png",
        "https://example.com/category/basic/banner2.png"
      ]
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

### 3. 获取实体详情

**请求地址**：`/entity/category-detail`
**请求方式**：GET
**是否需要 token**：否

**请求参数**：

| 参数名 | 类型 | 必填 | 描述 |
|-------|------|------|------|
| category_id | int | 是 | 实体分类ID |

**返回示例**：

```json
// 成功
{
  "code": 200,
  "message": "获取实体详情成功",
  "data": {
    "id": 1,
    "name": "基础款",
    "price": "99.00",
    "validity_period": 30,
    "render_count": 20,
    "description": "适合轻量体验",
    "image_url": "https://example.com/category/basic.png",
    "sort_order": 1,
    "placeholder": "请输入你的设计描述",
    "urls": [
      "https://example.com/category/basic/banner1.png",
      "https://example.com/category/basic/banner2.png"
    ]
  }
}

// 失败
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
```

### 4. 用户制作历史

**请求地址**：`/entity/make-history`
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
  "message": "获取用户制作历史成功",
  "data": [
    {
      "purchased_entity_id": 1,
      "category_id": 1,
      "category_name": "基础款",
      "order_id": 1,
      "remaining_renders": 17,
      "expire_time": "2026-04-23 12:35:00",
      "images": [
        {
          "image_url": "https://example.com/image.png",
          "render_url": "https://example.com/render.png",
          "corpus_id": 12,
          "prompt": "示例提示词"
        }
      ]
    }
  ]
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

**说明**（由后端自动处理，前端无需传）：

- 调用微信支付 Native 下单，金额取实体分类 `price`；
- `description`：由后端生成，格式为 `爱制-{分类名称}`；
- 支付单有效时间：默认 **10 分钟**（对应微信 `time_expire`）；
- 微信 `attach` 内为 JSON：`order_type` 固定为 `wx`，`extra` 为业务侧 `order_no`（商户订单号）。

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
    "qr_code_url": "weixin://wxpay/bizpayurl?pr=xxxxxx",
    "amount": 59.9,
    "description": "爱制-示例实体分类",
    "expire_time": "2026-03-28 12:40:00"
  }
}

// 成功（已有可用订单，无需支付）
{
  "code": 200,
  "message": "已有可用订单，无需支付",
  "data": {
    "order_id": 1,
    "order_no": "AM2026032412304599",
    "order_status": 0,
    "order_status_name": "待使用",
    "remaining_renders": 17,
    "expire_time": "2026-04-23 12:35:00"
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

当订单状态为 `2/3/4/5` 时，接口会返回该订单下 `aimade_generated_image.is_use=1` 的 `render_url`（用于展示当前制作中图片）；其它状态时 `render_url` 返回 `null`。

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
      "total_amount": "99.00",
      "render_url": null
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
 
### 4. 获取订单状态列表

**请求地址**：`/order/status-list`
**请求方式**：GET
**是否需要 token**：是
**请求头**：
- Authorization: Bearer {token}

**请求参数**：

| 参数名 | 类型 | 必填 | 描述 |
|-------|------|------|------|
| order_id | int | 是 | 订单ID（`aimade_entity_order.id`） |

**说明**：在 `aimade_order_status` 中通过 `order_id` 查询订单状态流水，按 `id` 正序返回。

**返回示例**：

```json
// 成功
{
  "code": 200,
  "message": "获取订单状态列表成功",
  "data": [
    {
      "id": 1,
      "order_id": 1001,
      "status": 0,
      "status_name": "待使用",
      "remark": "支付完成，订单进入待使用流程",
      "created_at": "2026-03-30 10:00:00"
    }
  ]
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

### 5. 基于渲染图下单

**请求地址**：`/order/place-order`
**请求方式**：POST
**是否需要 token**：是
**请求头**：
- Authorization: Bearer {token}

**请求参数**：

| 参数名 | 类型 | 必填 | 描述 |
|-------|------|------|------|
| image_id | int | 是 | `aimade_generated_image.id` |
| address_id | int | 是 | `aimade_user_address.id`（当前用户地址） |
| remark | string | 否 | 下单备注，写入 `aimade_order_status.remark`（不传则使用默认文案） |

**说明**：
- 通过 `aimade_generated_image` 找到 `corpus_id`，再通过 `aimade_order_corpus` 找到 `order_id`；
- 仅允许使用 `render_url` 已生成完成的图片下单（`render_url` 非空）；
- 将 `aimade_generated_image.is_use` 更新为 `1`；
- 将 `aimade_entity_order.order_status` 更新为 `2`（下单），并更新 `address_id`；
- 向 `aimade_order_status` 新增一条状态记录（`status=2`），`remark` 优先取请求参数；
- 下单成功后，将当前订单对应 `aimade_user_purchased_entity` 记录 `status` 置为 `0`，并将 `remaining_renders` 置为 `0`（后续不可再生成）。

**返回示例**：

```json
// 成功
{
  "code": 200,
  "message": "下单成功",
  "data": {
    "image_id": 10,
    "order_id": 1001,
    "order_status": 2,
    "order_status_name": "下单",
    "address_id": 3
  }
}

// 失败
{
  "code": 400,
  "message": "参数不完整",
  "data": null
}

{
  "code": 400,
  "message": "地址不存在或不可用",
  "data": null
}

{
  "code": 400,
  "message": "图片记录不存在或无权限",
  "data": null
}

{
  "code": 400,
  "message": "订单未支付，无法下单",
  "data": null
}

{
  "code": 400,
  "message": "渲染预览图尚未生成完成，无法下单",
  "data": null
}

{
  "code": 400,
  "message": "下单失败，请稍后重试",
  "data": null
}
```

### 5. 支付回调（测试伪回调）

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

### 5. 生成图片接口

**请求地址**：`/image/generate-image`
**请求方式**：POST
**是否需要 token**：是
**请求头**：
- Authorization: Bearer {token}

**请求参数**：

| 参数名 | 类型 | 必填 | 描述 |
|-------|------|------|------|
| category_id | int | 是 | 选中的实体分类ID |
| prompt | string | 是 | 生成提示词 |
| aspect_ratio | string | 否 | 宽高比，默认 `3:4` |
| share_to_community | int | 否 | 是否分享到社区（1：是，0：否，默认0） |

**说明**：接口会先进行资格校验，然后调用 Nano-Banana 生成服务，并新增写入 `aimade_order_corpus` 与 `aimade_generated_image`。`aimade_generated_image.query_id` 存储第三方返回的 `result['data']['id']`，用于后续结果查询。

当 `share_to_community=1` 时，会先在 `aimade_creative_community` 创建一条收录记录（`status=0`，待图片生成完成），其中 `image_id` 关联本次 `generated_image` 记录，标题取 `prompt` 前100字符，描述为 `prompt`。

生成参数固定为：`image_size='2K'`、`model='nano-banana'`、`shot_progress=false`（不需要前端传递）。

校验规则：
1. 当前用户在传入的 `category_id` 下存在 `payment_status = 1` 的订单；
2. 订单状态为 `待使用(0)` 或 `生成中(1)`；
3. 对应权益记录 `remaining_renders > 0` 且未过期（`expire_time > 当前时间`）；
4. **`prompt` 内容合规**：不允许包含涉黄、涉赌、涉毒、政治敏感等违规内容（服务端基于可配置关键词表做匹配，具体词表见 `config/prompt_sensitive.php`，可按业务扩充）。命中时直接返回失败提示，不调用第三方生成接口。

**返回示例**：

```json
// 成功（任务创建成功）
{
  "code": 200,
  "message": "图片生成任务创建成功",
  "data": {
    "query_id": "67d3f3b4f7a123456789abcd",
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
  "message": "当前无可用订单，请先购买或检查剩余生成次数",
  "data": null
}

{
  "code": 400,
  "message": "内容包含违规信息，请修改提示词后重试",
  "data": null
}

{
  "code": 400,
  "message": "生成图片请求失败，请稍后重试",
  "data": null
}

{
  "code": 400,
  "message": "生成任务创建失败，请稍后重试",
  "data": null
}
```

### 6. 查询图片生成状态

**请求地址**：`/image/get-image-result`
**请求方式**：POST
**是否需要 token**：是
**请求头**：
- Authorization: Bearer {token}

**请求参数**：

| 参数名 | 类型 | 必填 | 描述 |
|-------|------|------|------|
| query_id | string | 否 | 第三方生成图片返回的唯一ID（generate-image 接口返回） |
| render_query_id | string | 否 | 第三方渲染任务唯一ID（如有） |

> 至少传入一个：`query_id` 或 `render_query_id`

**说明**：调用 `NanoBananaService->getImageResult()` 查询当前任务状态。

- 当返回状态为 `running`：返回 `status=0`；
- 当返回状态为 `succeeded`：从 `result['data']['results'][0]['url']` 取出图片地址，并根据你传入的 id 把 `aimade_generated_image.image_url` 或 `aimade_generated_image.render_url` 回填，同时返回 `data.url`；若该任务在社区中有预创建记录（`status=0`），则会同步更新为 `status=1`。

**返回示例**：

```json
// 成功（生成中）
{
  "code": 200,
  "message": "查询图片生成结果成功",
  "data": {
    "status": 0,
    "message": "图片正在生成中"
  }
}

// 成功（生成成功）
{
  "code": 200,
  "message": "查询图片生成结果成功",
  "data": {
    "status": 1,
    "message": "图片生成成功",
    "url": "https://example.com/image.png",
    "image_id": "1234567890"
  }
}

// 失败
{
  "code": 400,
  "message": "参数不完整",
  "data": null
}

{
  "code": 400,
  "message": "图片任务不存在",
  "data": null
}
```

### 6.5. 生成最终实体渲染图

**请求地址**：`/image/generate-render-image`
**请求方式**：POST
**是否需要 token**：是
**请求头**：
- Authorization: Bearer {token}

**请求参数**：

| 参数名 | 类型 | 必填 | 描述 |
|-------|------|------|------|
| image_id | int | 是 | `aimade_generated_image.id`（原始设计图生成记录ID） |

**说明**：
- 先根据 `image_id` 找到原始生成图的 `image_url` 与关联的 `category_id`；
- 再从 `aimade_entity_render_config` 读取渲染所需：
  - `entity_image_url` 作为 `urls[0]`
  - `fixed_render_prompt` 作为 Nano-Banana 的 `prompt`
- 调用 Nano-Banana 生成最终实体渲染图，并在 `aimade_generated_image.render_query_id` 写入第三方返回的任务 ID；
- 调用方拿到 `render_query_id` 后，使用 `/image/get-image-result` 轮询（传 `render_query_id`）获取 `render_url`。

**返回示例**：

```json
// 成功（任务创建成功）
{
  "code": 200,
  "message": "实体渲染任务创建成功",
  "data": {
    "render_query_id": "67d3f3b4f7a123456789abcd"
  }
}

// 失败
{
  "code": 400,
  "message": "参数不完整",
  "data": null
}
```

### 7. 获取当前分类下用户的已生成图片

**请求地址**：`/image/generated-images`
**请求方式**：POST
**是否需要 token**：是
**请求头**：
- Authorization: Bearer {token}

**请求参数**：

| 参数名 | 类型 | 必填 | 描述 |
|-------|------|------|------|
| category_id | int | 是 | 实体分类ID |

**说明**：根据当前用户的 `aimade_entity_order`（`payment_status=1` 且 `order_status=1`：生成中）筛选已生成的图片，返回 `aimade_generated_image` 中对应记录的 `image_url/render_url/corpus_id`，以及 `aimade_order_corpus.prompt`。

**返回示例**：

```json
// 成功
{
  "code": 200,
  "message": "获取生成图片列表成功",
  "data": [
    {
      "image_id": 1,
      "image_url": "https://example.com/image.png",
      "render_url": "https://example.com/render.png",
      "corpus_id": 12,
      "prompt": "示例提示词"
    }
  ]
}

// 失败
{
  "code": 400,
  "message": "参数不完整",
  "data": null
}
```

### 8. 获取用户分享的创意社区图片

**请求地址**：`/image/shared-creatives`
**请求方式**：POST
**是否需要 token**：否

**请求参数**：

| 参数名 | 类型 | 必填 | 描述 |
|-------|------|------|------|
| imageId | string | 否 | base64 加密后的 `aimade_generated_image.id` 主键；传入后对应图片会被放在返回数组的第一个 |

**说明**：根据 `aimade_creative_community`（`status=1`）筛选已分享的创意图片，并联表 `aimade_generated_image` 与 `aimade_order_corpus` 获取展示数据。

当传入 `imageId` 时，会先解密并查出对应的 `aimade_generated_image` 记录，将其放在返回列表第一个；剩余图片仍按原排序查询，并自动避免重复。

接口字段说明：
- `aimade_generated_image`：只返回 `image_url/render_url`
- `aimade_creative_community`：返回 `creative_id/title/description/likes_count/views_count/is_public`
- `aimade_order_corpus`：返回 `corpus_id/prompt`

**返回示例**：
```json
// 成功
{
  "code": 200,
  "message": "获取用户分享的创意图片成功",
  "data": [
    {
      "image_url": "https://example.com/image.png",
      "render_url": "https://example.com/render.png",
      "creative_id": 1,
      "title": "示例标题",
      "description": "示例描述",
      "likes_count": 0,
      "views_count": 0,
      "is_public": 1,
      "corpus_id": 12,
      "prompt": "示例提示词"
    }
  ]
}

// 失败（无数据时一般仍返回 200，data 为 []）


```

## 创意社区模块

> **数据表**：`aimade_user_creative_favorite`（用户收藏）、关联 `aimade_creative_community` 与 `aimade_generated_image`。  
> **说明**：`aimade_generated_image` 表无 `description` 字段，列表中的 `description` 取自 `aimade_creative_community.description`（收录描述）。

### 1. 收藏社区图片

**请求地址**：`/community/favorite`  
**请求方式**：POST  
**是否需要 token**：是  
**请求头**：
- Authorization: Bearer {token}

**请求参数**：

| 参数名 | 类型 | 必填 | 描述 |
|-------|------|------|------|
| creative_community_id | int | 是 | 创意社区收录记录 ID（`aimade_creative_community.id`） |

**业务说明**：
- 仅允许收藏 `status=1`、`is_public=1` 且未软删的收录；关联生成图需有效。
- 同一用户对同一条收录仅一条有效收藏；已收藏时返回「已收藏」且不重复增加收录表的 `likes_count`。
- 首次收藏或从软删状态恢复收藏时，`aimade_creative_community.likes_count` 加 1。

**返回示例**：

```json
// 成功
{
  "code": 200,
  "message": "收藏成功",
  "data": {
    "id": 1
  }
}

// 已收藏（幂等）
{
  "code": 200,
  "message": "已收藏",
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
  "message": "社区收录不存在或不可收藏",
  "data": null
}

{
  "code": 400,
  "message": "关联图片不存在或不可用",
  "data": null
}

{
  "code": 400,
  "message": "收藏失败，请稍后重试",
  "data": null
}
```

### 2. 获取用户收藏的社区图片

**请求地址**：`/community/favorite-list`  
**请求方式**：GET  
**是否需要 token**：是  
**请求头**：
- Authorization: Bearer {token}

**请求参数**：

| 参数名 | 类型 | 必填 | 描述 |
|-------|------|------|------|
| page | int | 否 | 页码，默认 1 |
| per_page | int | 否 | 每页条数，默认 10，最大 50 |

**返回字段说明**（每条）：
- `image_url` / `render_url`：来自 `aimade_generated_image`
- `description`：来自 `aimade_creative_community.description`（收录描述）

**返回示例**：

```json
// 成功
{
  "code": 200,
  "message": "获取收藏列表成功",
  "data": {
    "list": [
      {
        "favorite_id": 1,
        "creative_community_id": 2,
        "image_id": 10,
        "image_url": "https://example.com/image.png",
        "render_url": "https://example.com/render.png",
        "description": "收录描述文案"
      }
    ],
    "total": 1,
    "page": 1,
    "per_page": 10
  }
}

// 失败
{
  "code": 401,
  "message": "请先登录",
  "data": null
}
```

### 3. 更新社区图片浏览量

**请求地址**：`/community/views`
**请求方式**：POST
**是否需要 token**：否

**请求参数**：

| 参数名 | 类型 | 必填 | 描述 |
|-------|------|------|------|
| id | int | 是 | 创意社区收录记录 ID（`aimade_creative_community.id`） |

**说明**：
- 使用 Redis 做 24 小时限流：同一 `用户 IP` 对某个 `id` 在 24 小时内只会计数 1 次（Redis 以“每个 IP 一个 key + 记录访问过的 id 列表”方式存储，避免大量 `ip+id` key 产生）；
- 未命中限流时，对 `aimade_creative_community.views_count` 自增 `+1`；
- 命中限流时跳过自增。

**返回示例**：
```json
// 首次访问（自增成功）
{
  "code": 200,
  "message": "访问量更新成功",
  "data": {
    "incremented": 1,
    "views_count": 123
  }
}

// 24 小时内重复访问（不自增）
{
  "code": 200,
  "message": "已跳过重复访问",
  "data": {
    "incremented": 0,
    "views_count": 123
  }
}
```

## 支付模块

> **说明**：业务侧「实体分类购买」已整合到订单模块接口 `POST /order/create-pay-qrcode`（见上文「订单模块 → 生成订单二维码」），由后端自动填充商品描述、过期时间、`attach` 等，前端只需传 `category_id`。本节为通用 Native 下单 Demo，便于单独联调微信支付。

#### 1. 创建Native支付订单

**请求地址**：`/pay/create-native-order`  
**请求方式**：POST  
**是否需要token**：是

**请求参数**：

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| amount | float | 是 | 支付金额（元） |
| description | string | 是 | 商品描述 |
| expire_minutes | int | 否 | 订单过期时间（分钟），默认30分钟 |
| order_type | string | 否 | 订单类型 |
| extra | object | 否 | 额外参数 |

**返回示例**：
```json
{
  "code": 200,
  "message": "订单创建成功",
  "data": {
    "order_no": "2024032812000012345678",
    "code_url": "weixin://wxpay/bizpayurl?pr=xxxxxx",
    "amount": 100.00,
    "description": "商品描述",
    "expire_time": "2024-03-28 12:30:00"
  }
}
```

#### 2. 查询订单状态

**请求地址**：`/pay/query-order`  
**请求方式**：POST  
**是否需要token**：是

**请求参数**：

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| order_no | string | 是 | 商户订单号 |

**返回示例**：
```json
{
  "code": 200,
  "message": "查询成功",
  "data": {
    "order_no": "2024032812000012345678",
    "status": "paid",
    "trade_state_desc": "支付成功",
    "amount": 100.00,
    "payer": {
      "openid": "oUpF8uMuAJO_M2pxb1Q9zNjWeS6o"
    }
  }
}
```

#### 3. 关闭订单

**请求地址**：`/pay/close-order`  
**请求方式**：POST  
**是否需要token**：是

**请求参数**：

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| order_no | string | 是 | 商户订单号 |

#### 4. 申请退款

**请求地址**：`/pay/refund`  
**请求方式**：POST  
**是否需要token**：是

**请求参数**：

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| order_no | string | 是 | 商户订单号 |
| refund_amount | float | 是 | 退款金额（元） |
| reason | string | 否 | 退款原因 |

**返回示例**：
```json
{
  "code": 200,
  "message": "退款申请已提交",
  "data": {
    "out_refund_no": "REF2024032812000012345678",
    "refund_id": "50300807092023111505682300045",
    "status": "PROCESSING"
  }
}
```

#### 5. 支付回调通知

**请求地址**：`/pay/notify`  
**请求方式**：POST  
**是否需要token**：否（微信服务器直接调用）

微信支付成功后，微信服务器会主动调用此接口通知支付结果。

**回调处理流程**：
1. 接收微信回调请求
2. 验证签名（确保请求来自微信）
3. 解密回调数据
4. 更新订单状态
5. 执行业务逻辑（如增加用户权益）
6. 返回成功响应

### 使用示例

#### 在控制器中使用

```php
<?php
namespace app\controller;

use app\BaseController;
use app\service\WechatPayService;

class OrderController extends BaseController
{
    protected $wechatPayService;
    
    public function __construct()
    {
        parent::__construct();
        $this->wechatPayService = new WechatPayService();
    }
    
    /**
     * 创建支付订单
     */
    public function createOrder()
    {
        try {
            // 创建支付订单
            $result = $this->wechatPayService->nativePay(
                'ORDER20240328001',           // 商户订单号
                100,                          // 金额（分）
                '测试商品',                    // 商品描述
                [
                    'attach' => json_encode(['user_id' => 1]),
                    'time_expire' => date('c', strtotime('+30 minutes'))
                ]
            );
            
            // 返回二维码链接
            return $this->success([
                'code_url' => $result['code_url']
            ]);
            
        } catch (\Exception $e) {
            return $this->error('创建订单失败：' . $e->getMessage());
        }
    }
}
```

#### 前端扫码支付流程

1. 调用 `/pay/create-native-order` 创建订单
2. 获取返回的 `code_url`（二维码链接）
3. 使用二维码生成库将 `code_url` 转换为二维码图片
4. 用户微信扫码支付
5. 前端轮询调用 `/pay/query-order` 查询支付状态
6. 支付成功后跳转成功页面

### 安全注意事项

1. **APIv3密钥**：妥善保管，不要泄露，定期更换
2. **商户证书**：私钥文件（apiclient_key.pem）必须安全存储
3. **回调验证**：务必验证微信回调的签名，防止伪造请求
4. **订单幂等性**：处理回调时注意订单幂等性，避免重复处理
5. **HTTPS**：生产环境必须使用HTTPS

### 常见问题

#### 1. 签名验证失败

- 检查APIv3密钥是否正确
- 检查商户证书路径是否正确
- 检查证书文件是否有读取权限

#### 2. 回调通知未收到

- 检查 `WECHAT_PAY_NOTIFY_URL` 是否可外网访问
- 确保回调地址使用HTTPS
- 检查服务器防火墙设置

#### 3. 证书问题

- 确保证书未过期
- 确保证书与商户号匹配
- 检查证书文件编码（应为UTF-8）

