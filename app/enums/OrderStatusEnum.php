<?php
declare (strict_types = 1);

namespace app\enums;

class OrderStatusEnum
{
    /**
     * 订单状态码 -> 状态名称
     * 与 dataBase.md 中 `order_status` 枚举含义保持一致
     * 0 初始化、1 生成中、2 下单、3 打样、4 生产、5 发货
     */
    public static function getName(int $status): string
    {
        return match ($status) {
            0 => '待使用',
            1 => '生成中',
            2 => '下单',
            3 => '打样',
            4 => '生产',
            5 => '发货',
            default => '未知状态',
        };
    }
}

