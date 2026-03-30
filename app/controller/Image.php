<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\enums\OrderStatusEnum;
use app\service\NanoBananaService;
use app\service\PromptContentChecker;
use think\facade\Db;

class Image extends BaseController
{
    protected NanoBananaService $nanoBananaService;

    protected function initialize()
    {
        $this->nanoBananaService = new NanoBananaService();
    }

    /**
     * 生成图片
     * @return \think\Response
     */
    public function generateImage()
    {
        $userId = $this->getCurrentUserId();
        $categoryId = (int)$this->request->post('category_id');
        $prompt = trim((string)$this->request->post('prompt', ''));
        $shareToCommunity = (int)$this->request->post('share_to_community', 0) === 1;
        if ($categoryId <= 0 || $prompt === '') {
            return $this->error('参数不完整');
        }

        $promptBlocked = PromptContentChecker::validatePrompt($prompt);
        if ($promptBlocked !== null) {
            return $this->error($promptBlocked);
        }

        $aspectRatio = (string)$this->request->post('aspect_ratio', '3:4');
        // 生成参数固定：与需求保持一致
        $imageSize = '2K';
        $model = 'nano-banana-fast';
        $shotProgress = false;

        $availableOrder = Db::name('entity_order')
            ->alias('o')
            ->join('user_purchased_entity upe', 'upe.order_id = o.id AND upe.user_id = o.user_id')
            ->field('o.id as order_id,o.order_no,o.order_status,upe.remaining_renders,upe.expire_time')
            ->where('o.user_id', $userId)
            ->where('o.category_id', $categoryId)
            ->where('o.payment_status', 1)
            ->where('o.order_status', 'in', [0, 1])
            ->where('o.status', 1)
            ->whereNull('o.deleted_at')
            ->where('upe.status', 1)
            ->whereNull('upe.deleted_at')
            ->where('upe.remaining_renders', '>', 0)
            ->where('upe.expire_time', '>', date('Y-m-d H:i:s'))
            ->order('o.id', 'desc')
            ->find();

        if (!$availableOrder) {
            return $this->error('当前无可用订单，请先购买或检查剩余生成次数');
        }

        try {
            // 调用服务发起图片生成
            $result = $this->nanoBananaService->generateImage(
                $prompt,
                $aspectRatio,
                $imageSize,
                $model,
                $shotProgress
            );
        } catch (\Throwable $e) {
            $this->writeLog('generate_image', '调用生成图片服务失败：' . $e->getMessage(), $userId, 3);
            return $this->error('生成图片请求失败，请稍后重试');
        }

        $queryId = (string)($result['data']['id'] ?? '');
        if ($queryId === '') {
            return $this->error('生成图片请求失败，请稍后重试');
        }

        // 兼容：如果前端传 render_query_id，但第三方当前只返回一个 id，
        // 这里先默认 render_query_id 与 query_id 相同，保证接口可用。
        // $renderQueryId = (string)(
        //     $result['data']['render_id']
        //     ?? $result['data']['renderId']
        //     ?? $queryId
        // );

        Db::startTrans();
        try {
            // 扣减一次剩余渲染次数（每次生成占用 1 次）
            $decrementRows = Db::name('user_purchased_entity')
                ->where('user_id', $userId)
                ->where('category_id', $categoryId)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->where('remaining_renders', '>', 0)
                ->where('expire_time', '>', date('Y-m-d H:i:s'))
                ->setDec('remaining_renders', 1);

            if ((int)$decrementRows <= 0) {
                throw new \RuntimeException('remaining_renders 扣减失败：次数不足或已过期');
            }

            // 第三方生成成功后，将订单状态置为“生成中”
            Db::name('entity_order')
                ->where('id', (int)$availableOrder['order_id'])
                ->where('user_id', $userId)
                ->where('status', 1)
                ->where('payment_status', 1)
                ->update(['order_status' => 1]);

            // 写入语料表
            $corpusId = Db::name('order_corpus')->insertGetId([
                'order_id' => (int)$availableOrder['order_id'],
                'prompt' => $prompt,
                'parameters' => json_encode([
                    'aspect_ratio' => $aspectRatio,
                    'image_size' => $imageSize,
                    'model' => $model,
                    'shot_progress' => $shotProgress
                ], JSON_UNESCAPED_UNICODE),
                'status' => 1
            ]);

            // 写入生成图片表，等待异步回查结果后补充真实URL/尺寸
            $generatedImageId = Db::name('generated_image')->insertGetId([
                'corpus_id' => $corpusId,
                'image_url' => '',
                'render_url' => null,
                'thumbnail_url' => null,
                'image_width' => null,
                'image_height' => null,
                'image_size' => null,
                'query_id' => $queryId,
                'render_query_id' => "",
                'status' => 1
            ]);

            // 勾选分享到社区时，创建社区收录记录
            if ($shareToCommunity) {
                $title = function_exists('mb_substr') ? mb_substr($prompt, 0, 100, 'UTF-8') : substr($prompt, 0, 100);
                if ($title === '') {
                    $title = 'AI生成作品';
                }

                Db::name('creative_community')->insert([
                    'image_id' => (int)$generatedImageId,
                    'user_id' => $userId,
                    'title' => $title,
                    'description' => $prompt,
                    'likes_count' => 0,
                    'views_count' => 0,
                    'is_public' => 1,
                    'status' => 0
                ]);
            }

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            $this->writeLog('generate_image', '生成图片落库失败：' . $e->getMessage(), $userId, 3);
            return $this->error('生成任务创建失败，请稍后重试');
        }

        $this->writeLog('generate_image', '用户发起生成图片成功', $userId);

        return $this->success([
            'query_id' => $queryId,
        ], '图片生成任务创建成功');
    }

    /**
     * 获取图片生成结果
     * @return \think\Response
     */
    public function getImageResult()
    {
        $userId = $this->getCurrentUserId();
        $queryId = trim((string)$this->request->post('query_id', ''));
        $renderQueryId = trim((string)$this->request->post('render_query_id', ''));

        if ($queryId === '' && $renderQueryId === '') {
            return $this->error('参数不完整');
        }

        // 优先使用 query_id；若只有 render_query_id 则用 render_query_id 查询
        $taskId = $queryId !== '' ? $queryId : $renderQueryId;

        // 查询当前用户的生成记录（只用来校验归属 + 拿到 corpus_id 便于落库）
        $generated = Db::name('generated_image')->alias('gi')
            ->join('order_corpus oc', 'oc.id = gi.corpus_id')
            ->join('entity_order o', 'o.id = oc.order_id')
            ->field('gi.id,gi.query_id,gi.render_query_id,gi.status,gi.image_url,gi.render_url,o.user_id,o.category_id')
            ->where('o.user_id', $userId)
            ->where('gi.status', 1)
            ->whereNull('gi.deleted_at')
            ->whereNull('oc.deleted_at')
            ->whereNull('o.deleted_at')
            ->where(function ($q) use ($queryId, $renderQueryId) {
                if ($queryId !== '') {
                    $q->where('gi.query_id', $queryId);
                }
                if ($renderQueryId !== '') {
                    $q->orWhere('gi.render_query_id', $renderQueryId);
                }
            })
            ->find();

        if (!$generated) {
            return $this->error('图片任务不存在');
        }

        // 防止频繁请求：如果表内已存在图片/渲染结果 url，则直接返回缓存
        $cachedUrl = '';
        if ($renderQueryId !== '') {
            $cachedUrl = (string)($generated['render_url'] ?? '');
            if ($cachedUrl === '') {
                // fallback：如果 render_url 为空但 image_url 已有值，也直接返回
                $cachedUrl = (string)($generated['image_url'] ?? '');
            }
        } elseif ($queryId !== '') {
            $cachedUrl = (string)($generated['image_url'] ?? '');
            if ($cachedUrl === '') {
                // fallback：如果 image_url 为空但 render_url 已有值，也直接返回
                $cachedUrl = (string)($generated['render_url'] ?? '');
            }
        }

        if ($cachedUrl !== '') {
            return $this->success([
                'status' => 1,
                'message' => '图片生成成功',
                'url' => $cachedUrl
            ], '查询图片生成结果成功');
        }

        try {
            $result = $this->nanoBananaService->getImageResult($taskId);
        } catch (\Throwable $e) {
            $this->writeLog('image_result_query', '查询图片生成结果失败：' . $e->getMessage(), $userId, 3);
            return $this->error('查询图片生成结果失败，请稍后重试');
        }

        $data = $result['data'] ?? [];
        $status = (string)($data['status'] ?? '');

        // running：继续轮询
        if ($status === 'running') {
            return $this->success([
                'status' => 0,
                'message' => '图片正在生成中'
            ], '查询图片生成结果成功');
        }

        // succeeded：回填图片信息
        if ($status === 'succeeded') {

            $url = $data['results'][0]['url'] ?? '';

            if ($renderQueryId !== '') {
                $update['render_url'] = $url;
            }elseif ($queryId !== '') {
                $update['image_url'] = $url;
            }

            Db::name('generated_image')->where('id', (int)$generated['id'])->update($update);

            // 图片真正生成成功后，公开到社区（将预创建记录置为有效）
            Db::name('creative_community')
                ->where('image_id', (int)$generated['id'])
                ->where('user_id', $userId)
                ->where('status', 0)
                ->update(['status' => 1]);

            $this->writeLog('image_result', '图片生成完成', $userId);

            return $this->success([
                'status' => 1,
                'message' => '图片生成成功',
                'url' => $url
            ], '查询图片生成结果成功');
        }

        // status != succeeded：返还一次 remaining_renders（避免重复返还：将 generated_image 标记失效）
        // 注意：若仍在 running 则不返还（否则会允许无限并发/重复扣减）。
        if ($status !== 'running') {
            $errorMsg = (string)($data['error'] ?? $data['failure_reason'] ?? $data['message'] ?? '图片生成失败');

            Db::startTrans();
            try {
                // 返还扣减次数
                Db::name('user_purchased_entity')
                    ->where('user_id', $userId)
                    ->where('category_id', (int)($generated['category_id'] ?? 0))
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->setInc('remaining_renders', 1);

                // 防重复返还：将该生成记录置为无效
                Db::name('generated_image')
                    ->where('id', (int)$generated['id'])
                    ->update(['status' => 0]);

                $this->writeLog(
                    'image_result_failed',
                    '图片生成失败，query_id=' . $taskId . ' error=' . $errorMsg,
                    $userId,
                    3
                );

                Db::commit();
            } catch (\Throwable $e) {
                Db::rollback();
                // 尽量不中断对外返回
                $this->writeLog(
                    'image_result_failed_refund_error',
                    '返还 remaining_renders 失败，query_id=' . $taskId . ' error=' . $e->getMessage(),
                    $userId,
                    3
                );
            }

            return $this->success([
                'status' => 0,
                'message' => '图片生成失败：' . $errorMsg
            ], '查询图片生成结果成功');
        }

        // 兜底：未知状态
        return $this->success([
            'status' => 0,
            'message' => '图片生成处理中'
        ], '查询图片生成结果成功');
    }

    /**
     * 获取用户分享的创意图片
     * @return \think\Response
     */
    public function sharedCreativeImages()
    {
        // 只展示已分享（status=1）的社区收录记录，并返回图片/渲染两类地址
        $list = Db::name('creative_community')->alias('cc')
            ->join('generated_image gi', 'gi.id = cc.image_id')
            ->join('order_corpus oc', 'oc.id = gi.corpus_id')
            ->field('cc.id as creative_id, cc.title, cc.description, cc.likes_count, cc.views_count, cc.is_public, oc.id as corpus_id, oc.prompt, gi.image_url, gi.render_url')
            ->where('cc.status', 1)
            ->whereNull('cc.deleted_at')
            ->where('gi.status', 1)
            ->whereNull('gi.deleted_at')
            ->where('oc.status', 1)
            ->whereNull('oc.deleted_at')
            ->order('cc.id', 'desc')
            ->select()
            ->toArray();

        return $this->success($list, '获取用户分享的创意图片成功');
    }
}

