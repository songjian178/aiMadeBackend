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
    // 修改密码
    Route::post('change-password', 'User/changePassword');
    // 禁用用户
    Route::post('disable', 'User/disableUser');
});
