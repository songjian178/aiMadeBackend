# 爱制平台 API 文档

## 接口新增记录

- 2026-03-23：新增用户模块接口

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
