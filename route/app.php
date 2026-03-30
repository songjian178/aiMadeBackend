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
    // 用户制作历史
    Route::get('make-history', 'Entity/makeHistory');
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

// 图片相关路由
Route::group('image', function () {
    // 生成图片资格校验
    Route::post('generate-image', 'Image/generateImage');
    // 查询图片生成结果
    Route::post('get-image-result', 'Image/getImageResult');
    // 获取当前分类下用户的已生成图片
    Route::post('generated-images', 'GeneratedImage/listByCategory');
})->middleware('auth');

// 图片相关公开路由（不需要 token）
Route::post('image/shared-creatives', 'Image/sharedCreativeImages');

// 创意社区（需登录）
Route::group('community', function () {
    Route::post('favorite', 'Community/favorite');
    Route::get('favorite-list', 'Community/favoriteList');
})->middleware('auth');

// 创意社区浏览量更新（公开接口，不需要 token）
Route::post('community/views', 'Community/updateViewsCount');

// 微信支付路由
Route::group('pay', function () {
    // 创建Native支付订单
    Route::post('create-native-order', 'Pay/createNativeOrder');
    // 查询订单状态
    Route::post('query-order', 'Pay/queryOrder');
    // 关闭订单
    Route::post('close-order', 'Pay/closeOrder');
    // 申请退款
    Route::post('refund', 'Pay/refund');
})->middleware('auth');

// 微信支付回调路由（不需要token，微信服务器直接调用）
Route::post('pay/notify', 'Pay/notify');

// 支付回调路由（第三方回调）
Route::group('order', function () {
    Route::post('pay-callback', 'Order/payCallback');
});
