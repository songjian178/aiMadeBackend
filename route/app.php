<?php

use think\facade\Route;

// 用户相关路由
Route::group('user', function () {
    // 获取邮箱验证码
    Route::post('get-email-code', 'User/getEmailCode');
    // 用户注册
    Route::post('register', 'User/register');
    // 用户登录
    Route::post('login', 'User/login');
});

Route::group('user', function () {
    // 修改密码
    Route::post('change-password', 'User/changePassword');
    // 禁用用户
    Route::post('disable', 'User/disableUser');
})->middleware('auth');

// 实体相关路由
Route::group('entity', function () {
    // 获取实体分类列表
    Route::get('category-list', 'Entity/categoryList');
});

Route::group('entity', function () {
    // 获取实体分类列表（含当前用户可使用次数）
    Route::get('category-list-with-usage', 'Entity/categoryListWithUsage');
})->middleware('auth');

// 地址相关路由
Route::group('address', function () {
    // 新增地址
    Route::post('create', 'Address/create');
    // 删除地址
    Route::post('delete', 'Address/delete');
    // 修改地址
    Route::post('update', 'Address/update');
    // 查询所有地址
    Route::get('list', 'Address/list');
})->middleware('auth');

// 订单相关路由
Route::group('order', function () {
    // 生成订单二维码
    Route::post('create-pay-qrcode', 'Order/createPayQrCode');
    // 订单心跳检测
    Route::post('heartbeat', 'Order/heartbeat');
    // 我的订单列表（已支付）
    Route::get('my-list', 'Order/myList');
})->middleware('auth');

// 支付回调路由（第三方回调）
Route::group('order', function () {
    Route::post('pay-callback', 'Order/payCallback');
});
