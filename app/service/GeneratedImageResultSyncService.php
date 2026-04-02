<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;

/**
 * 将 NanoBanana 任务结果同步到 aimade_generated_image，与 Image::getImageResult 业务一致。
 */
class GeneratedImageResultSyncService
{
    protected NanoBananaService $nanoBananaService;

    public function __construct(?NanoBananaService $nanoBananaService = null)
    {
        $this->nanoBananaService = $nanoBananaService ?? new NanoBananaService();
    }

    /**
     * @param array{image_id:int,user_id:int,category_id:int} $generated
     * @param array{operation_ip?:string,user_agent?:string}|null $logContext HTTP 请求时传入，便于日志记录真实 IP；为空则按 CLI 任务记
     * @return array{type:string,url?:string,message?:string}
     *   type: running | succeeded | failed | error
     */
    public function pollThirdPartyAndPersist(array $generated, string $taskId, bool $isRenderPoll, ?array $logContext = null): array
    {
        $imageId = (int)$generated['image_id'];
        $userId = (int)$generated['user_id'];
        $categoryId = (int)($generated['category_id'] ?? 0);
        $taskId = trim($taskId);
        if ($taskId === '') {
            return ['type' => 'error', 'message' => 'taskId 为空'];
        }

        try {
            $result = $this->nanoBananaService->getImageResult($taskId);
        } catch (\Throwable $e) {
            $this->writeTaskLog(
                'image_result_query',
                '查询图片生成结果失败：' . $e->getMessage(),
                $userId,
                3,
                $logContext
            );
            return ['type' => 'error', 'message' => $e->getMessage()];
        }

        $data = $result['data'] ?? [];
        $status = (string)($data['status'] ?? '');

        if ($status === 'running') {
            return ['type' => 'running'];
        }

        if ($status === 'succeeded') {
            $url = (string)($data['results'][0]['url'] ?? '');
            $update = $isRenderPoll ? ['render_url' => $url] : ['image_url' => $url];
            Db::name('generated_image')->where('id', $imageId)->update($update);

            Db::name('creative_community')
                ->where('image_id', $imageId)
                ->where('user_id', $userId)
                ->where('status', 0)
                ->update(['status' => 1]);

            $this->writeTaskLog('image_result', '图片生成完成', $userId, 1, $logContext);

            return ['type' => 'succeeded', 'url' => $url];
        }

        if ($status !== 'running') {
            $errorMsg = (string)($data['error'] ?? $data['failure_reason'] ?? $data['message'] ?? '图片生成失败');

            Db::startTrans();
            try {
                Db::name('user_purchased_entity')
                    ->where('user_id', $userId)
                    ->where('category_id', $categoryId)
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->setInc('remaining_renders', 1);

                if ($isRenderPoll) {
                    Db::name('generated_image')
                        ->where('id', $imageId)
                        ->update([
                            'render_query_id' => '',
                            'render_url' => null,
                            'status' => 1,
                        ]);
                } else {
                    Db::name('generated_image')
                        ->where('id', $imageId)
                        ->update(['status' => 0]);
                }

                $this->writeTaskLog(
                    'image_result_failed',
                    '图片生成失败，query_id=' . $taskId . ' error=' . $errorMsg,
                    $userId,
                    3,
                    $logContext
                );

                Db::commit();
            } catch (\Throwable $e) {
                Db::rollback();
                $this->writeTaskLog(
                    'image_result_failed_refund_error',
                    '返还 remaining_renders 失败，query_id=' . $taskId . ' error=' . $e->getMessage(),
                    $userId,
                    3,
                    $logContext
                );
            }

            return ['type' => 'failed', 'message' => $errorMsg];
        }

        return ['type' => 'running', 'message' => '图片生成处理中'];
    }

    private function writeTaskLog(
        string $operationType,
        string $content,
        int $userId,
        int $level = 1,
        ?array $logContext = null
    ): void {
        $ip = '0.0.0.0';
        $ua = 'generated-image-result-sync';
        if (is_array($logContext)) {
            if (isset($logContext['operation_ip']) && trim((string)$logContext['operation_ip']) !== '') {
                $ip = trim((string)$logContext['operation_ip']);
            }
            if (array_key_exists('user_agent', $logContext)) {
                $ua = (string)$logContext['user_agent'];
            }
        }
        try {
            Db::name('log')->insert([
                'user_id' => $userId > 0 ? $userId : null,
                'operation_type' => $operationType,
                'operation_ip' => $ip,
                'user_agent' => $ua,
                'operation_content' => $content,
                'log_level' => $level,
                'status' => 1,
            ]);
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
