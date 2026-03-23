<?php
declare (strict_types = 1);

namespace app\service;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use think\facade\Log;

/**
 * 邮件服务类
 */
class MailService
{
    /**
     * 发送邮箱验证码
     * @param string $email 邮箱地址
     * @param string $code 验证码
     * @return bool
     */
    public function sendVerificationCode(string $email, string $code): bool
    {
        try {
            $mail = new PHPMailer(true);
            
            // 服务器设置
            $mail->isSMTP();
            $mail->Host = 'smtp.163.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'mrsongopen@163.com';
            $mail->Password = 'VWiNGPhRMH2fZ259';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;
            $mail->CharSet = 'UTF-8';
            
            // 发件人
            $mail->setFrom('mrsongopen@163.com', '爱制平台');
            
            // 收件人
            $mail->addAddress($email);
            
            // 邮件内容
            $mail->isHTML(true);
            $mail->Subject = '【爱制】邮箱验证码';
            $mail->Body = "<p>您好！</p><p>您正在使用邮箱验证码功能，您的验证码是：<strong style='font-size: 18px; color: #ff6600;'>{$code}</strong></p><p>验证码有效期为10分钟，请尽快使用。</p><p>如非本人操作，请忽略此邮件。</p><p>爱制平台</p>";
            
            // 发送邮件
            $mail->send();
            return true;
        } catch (Exception $e) {
            // 记录错误日志
            	Log::error('邮件发送失败: ' . $e->getMessage());
            return false;
        }
    }
}
