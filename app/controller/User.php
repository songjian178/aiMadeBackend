<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\service\MailService;
use app\service\JwtService;
use think\facade\Db;
use think\exception\ValidateException;

class User extends BaseController
{
    /**
     * 获取邮箱验证码
     * @return \think\Response
     */
    public function getEmailCode()
    {
        $email = $this->request->post('email');
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('邮箱格式不正确');
        }
        
        // 检查邮箱是否已注册
        $user = Db::name('user')->where('email', $email)->find();
        if ($user) {
            return $this->error('该邮箱已注册');
        }
        
        // 生成6位随机验证码
        $code = mt_rand(100000, 999999);
        
        // 发送验证码
        $mailService = new MailService();
        if (!$mailService->sendVerificationCode($email, (string)$code)) {
            return $this->error('验证码发送失败，请稍后重试');
        }
        
        // 保存验证码到数据库
        $expireTime = time() + 600; // 10分钟过期
        Db::name('email_verification')->insert([
            'email' => $email,
            'code' => $code,
            'expire_time' => date('Y-m-d H:i:s', $expireTime),
            'is_used' => 0,
            'status' => 1
        ]);
        
        return $this->success(null, '验证码已发送，请注意查收');
    }
    
    /**
     * 用户注册
     * @return \think\Response
     */
    public function register()
    {
        $data = $this->request->post();
        
        // 验证参数
        if (empty($data['email']) || empty($data['code']) || empty($data['password']) || empty($data['confirm_password'])) {
            return $this->error('参数不完整');
        }
        
        if ($data['password'] !== $data['confirm_password']) {
            return $this->error('两次输入的密码不一致');
        }
        
        if (strlen($data['password']) < 6) {
            return $this->error('密码长度不能少于6位');
        }
        
        // 验证邮箱验证码
        $verification = Db::name('email_verification')
            ->where('email', $data['email'])
            ->where('code', $data['code'])
            ->where('is_used', 0)
            ->where('expire_time', '>', date('Y-m-d H:i:s'))
            ->where('status', 1)
            ->find();
        
        if (!$verification) {
            return $this->error('验证码无效或已过期');
        }
        
        // 检查邮箱是否已注册
        $user = Db::name('user')->where('email', $data['email'])->find();
        if ($user) {
            return $this->error('该邮箱已注册');
        }
        
        // 开始事务
        Db::startTrans();
        try {
            // 标记验证码为已使用
            Db::name('email_verification')->where('id', $verification['id'])->update([
                'is_used' => 1
            ]);
            
            // 创建用户
            $userData = [
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'username' => $data['username'] ?? $this->generateUsername($data['email']),
                'nickname' => $data['nickname'] ?? $this->generateUsername($data['email']),
                'role' => 0,
                'status' => 1
            ];
            
            $userId = Db::name('user')->insertGetId($userData);
            
            // 提交事务
            Db::commit();
            
            return $this->success(null, '注册成功');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return $this->error('注册失败，请稍后重试');
        }
    }
    
    /**
     * 用户登录
     * @return \think\Response
     */
    public function login()
    {
        $email = $this->request->post('email');
        $password = $this->request->post('password');
        
        if (empty($email) || empty($password)) {
            return $this->error('邮箱和密码不能为空');
        }
        
        // 查找用户
        $user = Db::name('user')
            ->where('email', $email)
            ->where('status', 1)
            ->find();
        
        if (!$user) {
            return $this->error('用户不存在或已被禁用');
        }
        
        // 验证密码
        if (!password_verify($password, $user['password'])) {
            return $this->error('密码错误');
        }
        
        // 更新最后登录时间
        Db::name('user')->where('id', $user['id'])->update([
            'last_login_time' => date('Y-m-d H:i:s')
        ]);
        
        // 生成token
        $token = $this->generateToken([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role']
        ]);
        
        // 返回用户信息和token
        return $this->success([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'username' => $user['username'],
                'nickname' => $user['nickname'],
                'avatar' => $user['avatar'],
                'role' => $user['role']
            ]
        ], '登录成功');
    }
    
    /**
     * 修改密码
     * @return \think\Response
     */
    public function changePassword()
    {
        $data = $this->request->post();
        $tokenData = $this->validateToken();
        $userId = is_object($tokenData['data']) ? (int)$tokenData['data']->user_id : (int)$tokenData['data']['user_id'];
        
        if($data['old_password'] == $data['new_password']){
            return $this->error('新旧密码不能一致', 401);
        }

        if (empty($data['old_password']) || empty($data['new_password']) || empty($data['confirm_password'])) {
            return $this->error('参数不完整');
        }
        
        if ($data['new_password'] !== $data['confirm_password']) {
            return $this->error('两次输入的新密码不一致');
        }
        
        if (strlen($data['new_password']) < 6) {
            return $this->error('新密码长度不能少于6位');
        }
        
        // 查找用户
        $user = Db::name('user')->where('id', $userId)->find();
        
        // 验证旧密码
        if (!password_verify($data['old_password'], $user['password'])) {
            return $this->error('旧密码错误');
        }
        
        // 更新密码
        $result = Db::name('user')->where('id', $userId)->update([
            'password' => password_hash($data['new_password'], PASSWORD_DEFAULT)
        ]);
        
        if ($result) {
            return $this->success(null, '密码修改成功');
        } else {
            return $this->error('密码修改失败，请稍后重试');
        }
    }
    
    /**
     * 禁用/启用用户
     * @return \think\Response
     */
    public function disableUser()
    {
        $tokenData = $this->validateToken();
        $tokenUser = is_object($tokenData['data']) ? (array)$tokenData['data'] : $tokenData['data'];
        
        // 检查是否为管理员
        if ((int)($tokenUser['role'] ?? 0) !== 1) {
            return $this->error('权限不足', 403);
        }
        
        $userId = $this->request->post('user_id');
        $status = $this->request->post('status');
        
        if (empty($userId) || !in_array($status, [0, 1])) {
            return $this->error('参数不完整');
        }
        
        // 不能禁用自己
        if ($userId == ($tokenUser['user_id'] ?? 0)) {
            return $this->error('不能操作自己的账户');
        }
        
        // 更新用户状态
        $result = Db::name('user')->where('id', $userId)->update([
            'status' => $status
        ]);
        
        if ($result) {
            return $this->success(null, $status == 0 ? '用户已禁用' : '用户已启用');
        } else {
            return $this->error('操作失败，请稍后重试');
        }
    }
    
    /**
     * 生成用户名
     * @param string $email
     * @return string
     */
    protected function generateUsername(string $email): string
    {
        $prefix = explode('@', $email)[0];
        $username = $prefix . '_' . mt_rand(1000, 9999);
        
        // 检查用户名是否已存在
        while (Db::name('user')->where('username', $username)->find()) {
            $username = $prefix . '_' . mt_rand(1000, 9999);
        }
        
        return $username;
    }
}
