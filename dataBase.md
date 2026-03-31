# 爱制 - 数据库设计文档

## 项目简介
爱制是一个基于AI的设计平台，提供一键生成和交付解决方案。致力于帮助每个人将创意变为现实，让设计变得简单且触手可及。

## 数据库表结构

### 1. 用户表 (aimade_user)

| 字段名 | 数据类型 | 长度 | 约束 | 描述 |
|-------|---------|------|------|------|
| id | int | 11 | PRIMARY KEY, AUTO_INCREMENT | 用户ID |
| username | varchar | 50 | NOT NULL, UNIQUE | 用户名 |
| email | varchar | 100 | NOT NULL, UNIQUE | 邮箱 |
| password | varchar | 255 | NOT NULL | 密码（加密存储） |
| last_login_time | datetime | - | NULL | 最后一次登录时间 |
| avatar | varchar | 255 | NULL | 头像URL |
| nickname | varchar | 50 | NULL | 昵称 |
| phone | varchar | 20 | NULL | 手机号 |
| role | tinyint | 1 | DEFAULT 0 | 角色（0：普通用户，1：管理员） |
| status | tinyint | 1 | DEFAULT 1 | 状态（1：正常，0：禁用） |
| created_at | datetime | - | DEFAULT CURRENT_TIMESTAMP | 创建时间 |
| updated_at | datetime | - | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | 更新时间 |
| deleted_at | datetime | - | NULL | 软删除时间 |

**索引**：
- PRIMARY KEY (id)
- UNIQUE KEY (username)
- UNIQUE KEY (email)
- KEY (phone)

### 2. 发送邮箱验证码表 (aimade_email_verification)

| 字段名 | 数据类型 | 长度 | 约束 | 描述 |
|-------|---------|------|------|------|
| id | int | 11 | PRIMARY KEY, AUTO_INCREMENT | 记录ID |
| email | varchar | 100 | NOT NULL | 邮箱 |
| code | varchar | 6 | NOT NULL | 验证码 |
| expire_time | datetime | - | NOT NULL | 过期时间 |
| is_used | tinyint | 1 | DEFAULT 0 | 是否已使用（0：未使用，1：已使用） |
| status | tinyint | 1 | DEFAULT 1 | 状态（1：有效，0：无效） |
| created_at | datetime | - | DEFAULT CURRENT_TIMESTAMP | 创建时间 |
| updated_at | datetime | - | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | 更新时间 |
| deleted_at | datetime | - | NULL | 软删除时间 |

**索引**：
- PRIMARY KEY (id)
- KEY (email)
- KEY (expire_time)

### 3. 生成实体分类表 (aimade_entity_category)

| 字段名 | 数据类型 | 长度 | 约束 | 描述 |
|-------|---------|------|------|------|
| id | int | 11 | PRIMARY KEY, AUTO_INCREMENT | 分类ID |
| name | varchar | 100 | NOT NULL | 实体名称 |
| price | decimal | 10,2 | NOT NULL | 金额 |
| validity_period | int | 11 | NOT NULL | 有效期（天） |
| render_count | int | 11 | NOT NULL | 可生成渲染的次数 |
| is_display | tinyint | 1 | DEFAULT 1 | 是否展示（1：展示，0：不展示） |
| description | text | - | NULL | 分类描述 |
| image_url | varchar | 255 | NULL | 分类图片URL |
| sort_order | int | 11 | DEFAULT 0 | 排序顺序 |
| status | tinyint | 1 | DEFAULT 1 | 状态（1：正常，0：禁用） |
| created_at | datetime | - | DEFAULT CURRENT_TIMESTAMP | 创建时间 |
| updated_at | datetime | - | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | 更新时间 |
| deleted_at | datetime | - | NULL | 软删除时间 |

**索引**：
- PRIMARY KEY (id)
- KEY (sort_order)
- KEY (is_display)

### 4. 生成订单表 (aimade_entity_order)

| 字段名 | 数据类型 | 长度 | 约束 | 描述 |
|-------|---------|------|------|------|
| id | int | 11 | PRIMARY KEY, AUTO_INCREMENT | 订单ID |
| user_id | int | 11 | NOT NULL | 用户ID（外键：aimade_user.id） |
| order_no | varchar | 50 | NOT NULL, UNIQUE | 订单编号 |
| category_id | int | 11 | NOT NULL | 购买权益ID（外键：aimade_entity_category.id） |
| total_amount | decimal | 10,2 | NOT NULL | 订单总金额 |
| address_id | int | 11 | NULL | 地址ID（外键：aimade_user_address.id） |
| payment_method | varchar | 20 | NULL | 支付方式 |
| payment_status | tinyint | 1 | DEFAULT 0 | 支付状态（0：未支付，1：已支付，2：支付失败） |
| order_status | tinyint | 1 | DEFAULT 0 | 订单状态（0：待使用，1：生成中，2：下单，3：打样，4：生产，5：发货） |
| status | tinyint | 1 | DEFAULT 1 | 状态（1：有效，0：无效） |
| created_at | datetime | - | DEFAULT CURRENT_TIMESTAMP | 创建时间 |
| updated_at | datetime | - | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | 更新时间 |
| deleted_at | datetime | - | NULL | 软删除时间 |

**索引**：
- PRIMARY KEY (id)
- UNIQUE KEY (order_no)
- KEY (user_id)
- KEY (category_id)
- KEY (payment_status)
- KEY (order_status)

### 5. 订单语料表 (aimade_order_corpus)

| 字段名 | 数据类型 | 长度 | 约束 | 描述 |
|-------|---------|------|------|------|
| id | int | 11 | PRIMARY KEY, AUTO_INCREMENT | 语料ID |
| order_id | int | 11 | NOT NULL | 订单ID（外键：aimade_entity_order.id） |
| prompt | text | - | NOT NULL | 提示词 |
| reference_image | varchar | 255 | NULL | 参考图片URL |
| parameters | json | - | NULL | 其他参数（JSON格式） |
| status | tinyint | 1 | DEFAULT 1 | 状态（1：有效，0：无效） |
| created_at | datetime | - | DEFAULT CURRENT_TIMESTAMP | 创建时间 |
| updated_at | datetime | - | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | 更新时间 |
| deleted_at | datetime | - | NULL | 软删除时间 |

**索引**：
- PRIMARY KEY (id)
- KEY (order_id)

### 6. 创意社区收录表 (aimade_creative_community)

| 字段名 | 数据类型 | 长度 | 约束 | 描述 |
|-------|---------|------|------|------|
| id | int | 11 | PRIMARY KEY, AUTO_INCREMENT | 收录ID |
| image_id | int | 11 | NOT NULL | 生成图片ID（外键：aimade_generated_image.id） |
| user_id | int | 11 | NOT NULL | 用户ID（外键：aimade_user.id） |
| title | varchar | 100 | NOT NULL | 标题 |
| description | text | - | NULL | 描述 |
| likes_count | int | 11 | DEFAULT 0 | 收藏数 |
| views_count | int | 11 | DEFAULT 0 | 浏览数 |
| is_public | tinyint | 1 | DEFAULT 1 | 是否公开（1：公开，0：私有） |
| status | tinyint | 1 | DEFAULT 1 | 状态（1：有效，0：无效） |
| created_at | datetime | - | DEFAULT CURRENT_TIMESTAMP | 创建时间 |
| updated_at | datetime | - | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | 更新时间 |
| deleted_at | datetime | - | NULL | 软删除时间 |

**索引**：
- PRIMARY KEY (id)
- KEY (image_id)
- KEY (user_id)
- KEY (likes_count)
- KEY (views_count)

### 6.1 用户创意社区收藏表 (aimade_user_creative_favorite)

| 字段名 | 数据类型 | 长度 | 约束 | 描述 |
|-------|---------|------|------|------|
| id | int | 11 | PRIMARY KEY, AUTO_INCREMENT | 收藏记录ID |
| user_id | int | 11 | NOT NULL | 用户ID（外键：aimade_user.id） |
| creative_community_id | int | 11 | NOT NULL | 创意社区收录ID（外键：aimade_creative_community.id） |
| status | tinyint | 1 | DEFAULT 1 | 状态（1：有效，0：无效） |
| created_at | datetime | - | DEFAULT CURRENT_TIMESTAMP | 创建时间 |
| updated_at | datetime | - | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | 更新时间 |
| deleted_at | datetime | - | NULL | 软删除时间 |

**索引**：
- PRIMARY KEY (id)
- KEY (user_id)
- KEY (creative_community_id)
- UNIQUE KEY (user_id, creative_community_id) | 同一用户对同一条社区收录仅保留一条收藏记录 |

### 7. 用户已购买实体表 (aimade_user_purchased_entity)

| 字段名 | 数据类型 | 长度 | 约束 | 描述 |
|-------|---------|------|------|------|
| id | int | 11 | PRIMARY KEY, AUTO_INCREMENT | 记录ID |
| user_id | int | 11 | NOT NULL | 用户ID（外键：aimade_user.id） |
| category_id | int | 11 | NOT NULL | 分类ID（外键：aimade_entity_category.id） |
| order_id | int | 11 | NOT NULL | 订单ID（外键：aimade_entity_order.id） |
| expire_time | datetime | - | NOT NULL | 过期时间 |
| remaining_renders | int | 11 | NOT NULL | 剩余渲染次数 |
| status | tinyint | 1 | DEFAULT 1 | 状态（1：有效，0：无效） |
| created_at | datetime | - | DEFAULT CURRENT_TIMESTAMP | 创建时间 |
| updated_at | datetime | - | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | 更新时间 |
| deleted_at | datetime | - | NULL | 软删除时间 |

**索引**：
- PRIMARY KEY (id)
- KEY (user_id)
- KEY (category_id)
- KEY (expire_time)
- UNIQUE KEY (user_id, category_id) | 确保用户对同一分类只存在一条有效记录 |

### 8. 生成图片表 (aimade_generated_image)

| 字段名 | 数据类型 | 长度 | 约束 | 描述 |
|-------|---------|------|------|------|
| id | int | 11 | PRIMARY KEY, AUTO_INCREMENT | 图片ID |
| corpus_id | int | 11 | NOT NULL | 使用语料ID（外键：aimade_order_corpus.id） |
| image_url | varchar | 255 | NOT NULL | 图片URL |
| render_url | varchar | 255 | NULL | 渲染图URL |
| thumbnail_url | varchar | 255 | NULL | 缩略图URL |
| image_width | int | 11 | NULL | 图片宽度 |
| image_height | int | 11 | NULL | 图片高度 |
| image_size | int | 11 | NULL | 图片大小（字节） |
| query_id | varchar | 255 | NULL | 第三方生成图片接口返回的唯一ID |
| render_query_id | varchar | 255 | NULL | 第三方生成渲染图片接口返回的唯一ID |
| is_use | tinyint | 2 | DEFAULT 0 | 是否使用下单（1：是，0：否） |
| status | tinyint | 1 | DEFAULT 1 | 状态（1：有效，0：无效） |
| created_at | datetime | - | DEFAULT CURRENT_TIMESTAMP | 创建时间 |
| updated_at | datetime | - | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | 更新时间 |
| deleted_at | datetime | - | NULL | 软删除时间 |

**索引**：
- PRIMARY KEY (id)
- KEY (corpus_id)

### 9. 用户地址表 (aimade_user_address)

| 字段名 | 数据类型 | 长度 | 约束 | 描述 |
|-------|---------|------|------|------|
| id | int | 11 | PRIMARY KEY, AUTO_INCREMENT | 地址ID |
| user_id | int | 11 | NOT NULL | 用户ID（外键：aimade_user.id） |
| recipient | varchar | 50 | NOT NULL | 收件人 |
| phone | varchar | 20 | NOT NULL | 手机号 |
| province | varchar | 50 | NOT NULL | 省份 |
| city | varchar | 50 | NOT NULL | 城市 |
| district | varchar | 50 | NOT NULL | 区县 |
| address | varchar | 255 | NOT NULL | 详细地址 |
| zip_code | varchar | 10 | NULL | 邮政编码 |
| is_default | tinyint | 1 | DEFAULT 0 | 是否默认地址（1：是，0：否） |
| status | tinyint | 1 | DEFAULT 1 | 状态（1：有效，0：无效） |
| created_at | datetime | - | DEFAULT CURRENT_TIMESTAMP | 创建时间 |
| updated_at | datetime | - | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | 更新时间 |
| deleted_at | datetime | - | NULL | 软删除时间 |

**索引**：
- PRIMARY KEY (id)
- KEY (user_id)
- KEY (is_default)

### 10. 订单状态表 (aimade_order_status)

| 字段名 | 数据类型 | 长度 | 约束 | 描述 |
|-------|---------|------|------|------|
| id | int | 11 | PRIMARY KEY, AUTO_INCREMENT | 记录ID |
| order_id | int | 11 | NOT NULL | 订单ID（外键：aimade_entity_order.id） |
| user_id | int | 11 | NOT NULL | 用户ID（外键：aimade_user.id） |
| status | tinyint | 1 | NOT NULL | 订单状态（0：待使用，1：生成中，2：下单，3：打样，4：生产，5：发货） |
| remark | text | - | NULL | 状态备注 |
| created_at | datetime | - | DEFAULT CURRENT_TIMESTAMP | 创建时间 |
| updated_at | datetime | - | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | 更新时间 |
| deleted_at | datetime | - | NULL | 软删除时间 |

**索引**：
- PRIMARY KEY (id)
- KEY (order_id)
- KEY (user_id)
- KEY (status)

### 11. 邀请活动详情表 (aimade_invitation_activity)

| 字段名 | 数据类型 | 长度 | 约束 | 描述 |
|-------|---------|------|------|------|
| id | int | 11 | PRIMARY KEY, AUTO_INCREMENT | 邀请ID |
| inviter_user_id | int | 11 | NOT NULL | 邀请人用户ID（外键：aimade_user.id） |
| invitee_user_id | int | 11 | NOT NULL | 被邀请人用户ID（外键：aimade_user.id） |
| invite_time | datetime | - | NOT NULL | 邀请时间 |
| invite_link | varchar | 255 | NOT NULL | 邀请链接 |
| is_activated | tinyint | 1 | DEFAULT 0 | 是否激活（1：是，0：否） |
| reward_status | tinyint | 1 | DEFAULT 0 | 奖励状态（0：未发放，1：已发放） |
| status | tinyint | 1 | DEFAULT 1 | 状态（1：有效，0：无效） |
| created_at | datetime | - | DEFAULT CURRENT_TIMESTAMP | 创建时间 |
| updated_at | datetime | - | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | 更新时间 |
| deleted_at | datetime | - | NULL | 软删除时间 |

**索引**：
- PRIMARY KEY (id)
- KEY (inviter_user_id)
- KEY (invitee_user_id)
- UNIQUE KEY (invitee_user_id) | 确保一个用户只能被邀请一次 |

### 12. 实体发货表 (aimade_entity_shipping)

| 字段名 | 数据类型 | 长度 | 约束 | 描述 |
|-------|---------|------|------|------|
| id | int | 11 | PRIMARY KEY, AUTO_INCREMENT | 发货ID |
| order_id | int | 11 | NOT NULL | 订单ID（外键：aimade_entity_order.id） |
| address_id | int | 11 | NOT NULL | 地址ID（外键：aimade_user_address.id） |
| tracking_number | varchar | 50 | NOT NULL | 快递单号 |
| shipping_company | varchar | 50 | NOT NULL | 快递公司 |
| shipping_time | datetime | - | NULL | 发货时间 |
| estimated_delivery_time | datetime | - | NULL | 预计送达时间 |
| actual_delivery_time | datetime | - | NULL | 实际送达时间 |
| status | tinyint | 1 | DEFAULT 1 | 状态（1：有效，0：无效） |
| created_at | datetime | - | DEFAULT CURRENT_TIMESTAMP | 创建时间 |
| updated_at | datetime | - | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | 更新时间 |
| deleted_at | datetime | - | NULL | 软删除时间 |

**索引**：
- PRIMARY KEY (id)
- KEY (order_id)
- KEY (address_id)
- UNIQUE KEY (tracking_number)

### 13. 收银表 (aimade_payment)

| 字段名 | 数据类型 | 长度 | 约束 | 描述 |
|-------|---------|------|------|------|
| id | int | 11 | PRIMARY KEY, AUTO_INCREMENT | 支付ID |
| order_id | int | 11 | NOT NULL | 订单ID（外键：aimade_entity_order.id） |
| user_purchased_entity_id | int | 11 | NULL | 用户已购买实体ID（外键：aimade_user_purchased_entity.id） |
| amount | decimal | 10,2 | NOT NULL | 支付金额 |
| payment_method | varchar | 20 | NOT NULL | 支付方式 |
| payment_transaction_id | varchar | 100 | NULL | 支付交易ID |
| payment_status | tinyint | 1 | DEFAULT 0 | 支付状态（0：未支付，1：已支付，2：支付失败） |
| payment_time | datetime | - | NULL | 支付时间 |
| refund_status | tinyint | 1 | DEFAULT 0 | 退款状态（0：未退款，1：已退款） |
| status | tinyint | 1 | DEFAULT 1 | 状态（1：有效，0：无效） |
| created_at | datetime | - | DEFAULT CURRENT_TIMESTAMP | 创建时间 |
| updated_at | datetime | - | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | 更新时间 |
| deleted_at | datetime | - | NULL | 软删除时间 |

**索引**：
- PRIMARY KEY (id)
- KEY (order_id)
- KEY (user_purchased_entity_id)
- KEY (payment_status)
- UNIQUE KEY (payment_transaction_id)

### 14. 日志表 (aimade_log)

| 字段名 | 数据类型 | 长度 | 约束 | 描述 |
|-------|---------|------|------|------|
| id | int | 11 | PRIMARY KEY, AUTO_INCREMENT | 日志ID |
| user_id | int | 11 | NULL | 用户ID（外键：aimade_user.id） |
| operation_type | varchar | 50 | NOT NULL | 操作类型 |
| operation_ip | varchar | 50 | NOT NULL | 操作IP |
| user_agent | text | - | NULL | 用户代理 |
| operation_content | text | - | NOT NULL | 操作内容 |
| log_level | tinyint | 1 | DEFAULT 1 | 日志等级（1：info，2：warning，3：error） |
| status | tinyint | 1 | DEFAULT 1 | 状态（1：有效，0：无效） |
| created_at | datetime | - | DEFAULT CURRENT_TIMESTAMP | 创建时间 |
| updated_at | datetime | - | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | 更新时间 |
| deleted_at | datetime | - | NULL | 软删除时间 |

**索引**：
- PRIMARY KEY (id)
- KEY (user_id)
- KEY (operation_type)
- KEY (log_level)
- KEY (created_at)

## 表关联关系

### 1. 用户相关
- `aimade_user` ↔ `aimade_email_verification`：一对多（一个用户可以有多个验证码）
- `aimade_user` ↔ `aimade_entity_order`：一对多（一个用户可以有多个订单）
- `aimade_user` ↔ `aimade_creative_community`：一对多（一个用户可以有多个创意社区收录）
- `aimade_user` ↔ `aimade_user_purchased_entity`：一对多（一个用户可以购买多个实体）
- `aimade_user` ↔ `aimade_user_address`：一对多（一个用户可以有多个地址）
- `aimade_user` ↔ `aimade_order_status`：一对多（一个用户可以有多个订单状态记录）
- `aimade_user` ↔ `aimade_invitation_activity`：一对多（一个用户可以邀请多个用户）
- `aimade_user` ↔ `aimade_log`：一对多（一个用户可以有多个操作日志）
- `aimade_user` ↔ `aimade_user_creative_favorite`：一对多（一个用户可收藏多条社区收录）

### 2. 订单相关
- `aimade_entity_order` ↔ `aimade_order_corpus`：一对多（一个订单可以有多个语料）
- `aimade_entity_order` ↔ `aimade_user_purchased_entity`：一对多（一个订单可以对应多个购买实体）
- `aimade_entity_order` ↔ `aimade_order_status`：一对多（一个订单可以有多个状态记录）
- `aimade_entity_order` ↔ `aimade_entity_shipping`：一对一（一个订单对应一个发货记录）
- `aimade_entity_order` ↔ `aimade_payment`：一对一（一个订单对应一个支付记录）

### 3. 图片相关
- `aimade_order_corpus` ↔ `aimade_generated_image`：一对多（一个语料可以生成多个图片）
- `aimade_generated_image` ↔ `aimade_creative_community`：一对一（一个创意社区收录对应一个生成图片）
- `aimade_creative_community` ↔ `aimade_user_creative_favorite`：一对多（一条社区收录可被多个用户收藏）

### 4. 分类相关
- `aimade_entity_category` ↔ `aimade_entity_order`：一对多（一个分类可以有多个订单）
- `aimade_entity_category` ↔ `aimade_user_purchased_entity`：一对多（一个分类可以被多个用户购买）

### 5. 地址相关
- `aimade_user_address` ↔ `aimade_entity_order`：一对多（一个地址可以用于多个订单）
- `aimade_user_address` ↔ `aimade_entity_shipping`：一对多（一个地址可以用于多个发货记录）

## 数据库设计说明

1. **表前缀**：所有表都使用 `aimade` 前缀，便于区分其他项目的表

2. **默认字段**：每张表都包含以下默认字段：
   - `id`：主键，自增
   - `status`：状态字段，默认值为1（有效）
   - `created_at`：创建时间，默认当前时间
   - `updated_at`：更新时间，默认当前时间且自动更新
   - `deleted_at`：软删除时间，默认为NULL

3. **字段设计**：
   - 字符串类型：根据实际需要设置合理长度
   - 数字类型：根据数据范围选择合适的类型
   - 日期时间类型：使用datetime类型存储时间信息
   - JSON类型：用于存储复杂的参数信息

4. **索引设计**：
   - 主键索引：每个表的id字段
   - 唯一索引：确保唯一性的字段（如订单编号、邮箱等）
   - 普通索引：用于频繁查询的字段（如user_id、order_id等）

5. **外键关系**：
   - 所有外键都指向对应表的主键
   - 外键关系确保数据的完整性和一致性

6. **软删除**：
   - 使用deleted_at字段实现软删除，便于数据恢复
   - 查询时需要过滤deleted_at为NULL的记录

7. **数据安全**：
   - 密码字段使用加密存储
   - 敏感信息（如手机号）可以考虑加密存储

8. **性能优化**：
   - 合理的索引设计
   - 适当的字段类型选择
   - 避免过度使用TEXT类型字段

## 后续建议

1. **数据库初始化**：
   - 创建数据库时使用utf8mb4字符集，支持更广泛的字符
   - 设置合适的数据库引擎（推荐InnoDB）

2. **数据迁移**：
   - 使用数据库迁移工具管理表结构变更
   - 记录所有表结构的变更历史

3. **数据备份**：
   - 定期备份数据库
   - 制定数据恢复策略

4. **性能监控**：
   - 监控数据库性能
   - 优化慢查询

5. **安全性**：
   - 定期更新数据库密码
   - 限制数据库访问权限
   - 防止SQL注入攻击

以上数据库设计文档涵盖了爱制平台的核心数据结构，为后续的开发和维护提供了基础。

## SQL 建表语句

### 1. 用户表 (aimade_user)

```sql
CREATE TABLE `aimade_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `email` varchar(100) NOT NULL COMMENT '邮箱',
  `password` varchar(255) NOT NULL COMMENT '密码（加密存储）',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后一次登录时间',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像URL',
  `nickname` varchar(50) DEFAULT NULL COMMENT '昵称',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `role` tinyint(1) DEFAULT '0' COMMENT '角色（0：普通用户，1：管理员）',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态（1：正常，0：禁用）',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';
```

### 2. 发送邮箱验证码表 (aimade_email_verification)

```sql
CREATE TABLE `aimade_email_verification` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `email` varchar(100) NOT NULL COMMENT '邮箱',
  `code` varchar(6) NOT NULL COMMENT '验证码',
  `expire_time` datetime NOT NULL COMMENT '过期时间',
  `is_used` tinyint(1) DEFAULT '0' COMMENT '是否已使用（0：未使用，1：已使用）',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态（1：有效，0：无效）',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `expire_time` (`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='发送邮箱验证码表';
```

### 3. 生成实体分类表 (aimade_entity_category)

```sql
CREATE TABLE `aimade_entity_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分类ID',
  `name` varchar(100) NOT NULL COMMENT '实体名称',
  `price` decimal(10,2) NOT NULL COMMENT '金额',
  `validity_period` int(11) NOT NULL COMMENT '有效期（天）',
  `render_count` int(11) NOT NULL COMMENT '可生成渲染的次数',
  `is_display` tinyint(1) DEFAULT '1' COMMENT '是否展示（1：展示，0：不展示）',
  `description` text COMMENT '分类描述',
  `image_url` varchar(255) DEFAULT NULL COMMENT '分类图片URL',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序顺序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态（1：正常，0：禁用）',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  KEY `sort_order` (`sort_order`),
  KEY `is_display` (`is_display`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='生成实体分类表';
```

### 4. 生成订单表 (aimade_entity_order)

```sql
CREATE TABLE `aimade_entity_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '订单ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `order_no` varchar(50) NOT NULL COMMENT '订单编号',
  `category_id` int(11) NOT NULL COMMENT '购买权益ID',
  `total_amount` decimal(10,2) NOT NULL COMMENT '订单总金额',
  `address_id` int(11) DEFAULT NULL COMMENT '地址ID',
  `payment_method` varchar(20) DEFAULT NULL COMMENT '支付方式',
  `payment_status` tinyint(1) DEFAULT '0' COMMENT '支付状态（0：未支付，1：已支付，2：支付失败）',
  `order_status` tinyint(1) DEFAULT '0' COMMENT '订单状态（0：待使用，1：生成中，2：下单，3：打样，4：生产，5：发货）',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态（1：有效，0：无效）',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `user_id` (`user_id`),
  KEY `category_id` (`category_id`),
  KEY `payment_status` (`payment_status`),
  KEY `order_status` (`order_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='生成订单表';
```

### 5. 订单语料表 (aimade_order_corpus)

```sql
CREATE TABLE `aimade_order_corpus` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '语料ID',
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `prompt` text NOT NULL COMMENT '提示词',
  `reference_image` varchar(255) DEFAULT NULL COMMENT '参考图片URL',
  `parameters` json DEFAULT NULL COMMENT '其他参数（JSON格式）',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态（1：有效，0：无效）',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单语料表';
```

### 6. 创意社区收录表 (aimade_creative_community)

```sql
CREATE TABLE `aimade_creative_community` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '收录ID',
  `image_id` int(11) NOT NULL COMMENT '生成图片ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `title` varchar(100) NOT NULL COMMENT '标题',
  `description` text COMMENT '描述',
  `likes_count` int(11) DEFAULT '0' COMMENT '点赞数',
  `views_count` int(11) DEFAULT '0' COMMENT '浏览数',
  `is_public` tinyint(1) DEFAULT '1' COMMENT '是否公开（1：公开，0：私有）',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态（1：有效，0：无效）',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  KEY `image_id` (`image_id`),
  KEY `user_id` (`user_id`),
  KEY `likes_count` (`likes_count`),
  KEY `views_count` (`views_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='创意社区收录表';
```

### 6.1 用户创意社区收藏表 (aimade_user_creative_favorite)

```sql
CREATE TABLE `aimade_user_creative_favorite` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '收藏记录ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `creative_community_id` int(11) NOT NULL COMMENT '创意社区收录ID',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态（1：有效，0：无效）',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_community` (`user_id`,`creative_community_id`),
  KEY `user_id` (`user_id`),
  KEY `creative_community_id` (`creative_community_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户创意社区收藏表';
```

### 7. 用户已购买实体表 (aimade_user_purchased_entity)

```sql
CREATE TABLE `aimade_user_purchased_entity` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `category_id` int(11) NOT NULL COMMENT '分类ID',
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `expire_time` datetime NOT NULL COMMENT '过期时间',
  `remaining_renders` int(11) NOT NULL COMMENT '剩余渲染次数',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态（1：有效，0：无效）',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `category_id` (`category_id`),
  KEY `expire_time` (`expire_time`),
  UNIQUE KEY `user_category` (`user_id`,`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户已购买实体表';
```

### 8. 生成图片表 (aimade_generated_image)

```sql
CREATE TABLE `aimade_generated_image` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '图片ID',
  `corpus_id` int(11) NOT NULL COMMENT '使用语料ID',
  `image_url` varchar(255) NOT NULL COMMENT '图片URL',
  `render_url` varchar(255) DEFAULT NULL COMMENT '渲染图URL',
  `thumbnail_url` varchar(255) DEFAULT NULL COMMENT '缩略图URL',
  `image_width` int(11) DEFAULT NULL COMMENT '图片宽度',
  `image_height` int(11) DEFAULT NULL COMMENT '图片高度',
  `image_size` int(11) DEFAULT NULL COMMENT '图片大小（字节）',
  `query_id` varchar(255)  NOT NULL COMMENT '第三方生成图片接口返回的唯一ID',
  `render_query_id` varchar(255)  NOT NULL COMMENT '第三方生成渲染图片接口返回的唯一ID',
  `is_use` tinyint(2) DEFAULT '0' COMMENT '是否使用下单（1：是，0：否）',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态（1：有效，0：无效）',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  KEY `corpus_id` (`corpus_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='生成图片表';
```

### 9. 用户地址表 (aimade_user_address)

```sql
CREATE TABLE `aimade_user_address` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '地址ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `recipient` varchar(50) NOT NULL COMMENT '收件人',
  `phone` varchar(20) NOT NULL COMMENT '手机号',
  `province` varchar(50) NOT NULL COMMENT '省份',
  `city` varchar(50) NOT NULL COMMENT '城市',
  `district` varchar(50) NOT NULL COMMENT '区县',
  `address` varchar(255) NOT NULL COMMENT '详细地址',
  `zip_code` varchar(10) DEFAULT NULL COMMENT '邮政编码',
  `is_default` tinyint(1) DEFAULT '0' COMMENT '是否默认地址（1：是，0：否）',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态（1：有效，0：无效）',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `is_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户地址表';
```

### 10. 订单状态表 (aimade_order_status)

```sql
CREATE TABLE `aimade_order_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `status` tinyint(1) NOT NULL COMMENT '订单状态（0：待使用，1：生成中，2：下单，3：打样，4：生产，5：发货）',
  `remark` text COMMENT '状态备注',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单状态表';
```

### 11. 邀请活动详情表 (aimade_invitation_activity)

```sql
CREATE TABLE `aimade_invitation_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '邀请ID',
  `inviter_user_id` int(11) NOT NULL COMMENT '邀请人用户ID',
  `invitee_user_id` int(11) NOT NULL COMMENT '被邀请人用户ID',
  `invite_time` datetime NOT NULL COMMENT '邀请时间',
  `invite_link` varchar(255) NOT NULL COMMENT '邀请链接',
  `is_activated` tinyint(1) DEFAULT '0' COMMENT '是否激活（1：是，0：否）',
  `reward_status` tinyint(1) DEFAULT '0' COMMENT '奖励状态（0：未发放，1：已发放）',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态（1：有效，0：无效）',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  KEY `inviter_user_id` (`inviter_user_id`),
  UNIQUE KEY `invitee_user_id` (`invitee_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邀请活动详情表';
```

### 12. 实体发货表 (aimade_entity_shipping)

```sql
CREATE TABLE `aimade_entity_shipping` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '发货ID',
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `address_id` int(11) NOT NULL COMMENT '地址ID',
  `tracking_number` varchar(50) NOT NULL COMMENT '快递单号',
  `shipping_company` varchar(50) NOT NULL COMMENT '快递公司',
  `shipping_time` datetime DEFAULT NULL COMMENT '发货时间',
  `estimated_delivery_time` datetime DEFAULT NULL COMMENT '预计送达时间',
  `actual_delivery_time` datetime DEFAULT NULL COMMENT '实际送达时间',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态（1：有效，0：无效）',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `address_id` (`address_id`),
  UNIQUE KEY `tracking_number` (`tracking_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='实体发货表';
```

### 13. 收银表 (aimade_payment)

```sql
CREATE TABLE `aimade_payment` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '支付ID',
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `user_purchased_entity_id` int(11) DEFAULT NULL COMMENT '用户已购买实体ID',
  `amount` decimal(10,2) NOT NULL COMMENT '支付金额',
  `payment_method` varchar(20) NOT NULL COMMENT '支付方式',
  `payment_transaction_id` varchar(100) DEFAULT NULL COMMENT '支付交易ID',
  `payment_status` tinyint(1) DEFAULT '0' COMMENT '支付状态（0：未支付，1：已支付，2：支付失败）',
  `payment_time` datetime DEFAULT NULL COMMENT '支付时间',
  `refund_status` tinyint(1) DEFAULT '0' COMMENT '退款状态（0：未退款，1：已退款）',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态（1：有效，0：无效）',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `user_purchased_entity_id` (`user_purchased_entity_id`),
  KEY `payment_status` (`payment_status`),
  UNIQUE KEY `payment_transaction_id` (`payment_transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='收银表';
```

### 14. 日志表 (aimade_log)

```sql
CREATE TABLE `aimade_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `operation_type` varchar(50) NOT NULL COMMENT '操作类型',
  `operation_ip` varchar(50) NOT NULL COMMENT '操作IP',
  `user_agent` text COMMENT '用户代理',
  `operation_content` text NOT NULL COMMENT '操作内容',
  `log_level` tinyint(1) DEFAULT '1' COMMENT '日志等级（1：info，2：warning，3：error）',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态（1：有效，0：无效）',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `operation_type` (`operation_type`),
  KEY `log_level` (`log_level`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='日志表';
```

### 15. 实体渲染配置表 (aimade_entity_render_config)

该表用于为每个“实体分类（aimade_entity_category.id）”配置：
- 实体图片：被渲染的物体图片 URL（例如：T 恤/杯子/海报模板等）
- 固定渲染 prompt：将用户设计内容渲染到实体图片上的固定提示词模板

```sql
CREATE TABLE `aimade_entity_render_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  `category_id` int(11) NOT NULL COMMENT '实体分类ID（外键：aimade_entity_category.id）',
  `entity_image_url` varchar(255) NOT NULL COMMENT '实体图片URL（被渲染物体）',
  `fixed_render_prompt` text NOT NULL COMMENT '固定渲染 prompt（用于渲染实体图片）',
  `parameters` json DEFAULT NULL COMMENT '渲染固定参数（JSON格式，可扩充）',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态（1：有效，0：无效）',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_id_unique` (`category_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='实体渲染配置表';
```

### 创建数据库

```sql
-- 创建数据库
CREATE DATABASE IF NOT EXISTS `aimade` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `aimade`;
```

### 注意事项

1. **字符集**：所有表使用 `utf8mb4` 字符集，支持完整的 Unicode 字符
2. **引擎**：使用 `InnoDB` 引擎，支持事务和外键约束
3. **排序规则**：使用 `utf8mb4_unicode_ci` 排序规则
4. **时间字段**：所有时间字段使用 `datetime` 类型
5. **JSON字段**：`parameters` 字段使用 JSON 类型，需要 MySQL 5.7.8+ 版本支持
6. **索引**：已为所有外键字段和常用查询字段添加索引
7. **软删除**：所有表都包含 `deleted_at` 字段用于软删除

### 使用说明

1. **执行顺序**：建议按照表之间的依赖关系顺序执行建表语句
2. **外键约束**：如果需要添加外键约束，可以在建表后使用 ALTER TABLE 添加
3. **数据导入**：建表完成后，可以导入初始数据
4. **权限设置**：确保数据库用户有创建表的权限

以上 SQL 语句可以直接在数据库管理工具中执行，创建所有表结构。