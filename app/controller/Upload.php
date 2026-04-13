<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;

use app\service\OssUploadService;
use think\file\UploadedFile;
use think\facade\Db;

class Upload extends BaseController
{
    /**
     * 图片上传至阿里云 OSS（multipart 字段名：file）
     */
    public function image()
    {
        $userId = $this->getCurrentUserId();
        $hasAvailableOrder = Db::name('entity_order')
            ->where('user_id', $userId)
            ->where('payment_status', 1)
            ->where('order_status', 'in', [0, 1])
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->find();
        if (!$hasAvailableOrder) {
            return $this->error('当前无可用权益，暂不支持上传');
        }

        /** @var UploadedFile|null $file */
        $file = $this->request->file('file');
        if (!$file instanceof UploadedFile) {
            return $this->error('请上传文件');
        }

        if (!$file->isValid()) {
            return $this->error('文件上传无效或未完整上传');
        }

        $ext = strtolower($file->getOriginalExtension());
        if ($ext === '') {
            return $this->error('无法识别文件后缀');
        }

        if (!in_array($ext, OssUploadService::allowedImageExtensions(), true)) {
            return $this->error('仅支持图片格式：' . implode('、', OssUploadService::allowedImageExtensions()));
        }

        $service = new OssUploadService();

        try {
            $out = $service->uploadImageFile($file->getPathname(), $ext);
        } catch (\Throwable $e) {
            $this->writeLog('oss_upload', '图片上传失败：' . $e->getMessage(), $userId, 3);
            return $this->error($e->getMessage());
        }

        $this->writeLog('oss_upload', '图片上传成功，key=' . ($out['key'] ?? ''), $userId);

        return $this->success([
            'url' => $out['url'],
            'key' => $out['key'],
        ], '上传成功');
    }
}
