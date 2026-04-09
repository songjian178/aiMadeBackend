<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\enums\OrderStatusEnum;
use app\service\GeneratedImageResultSyncService;
use app\service\NanoBananaService;
use app\service\PromptContentChecker;
use think\facade\Db;
use think\facade\Cache;

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
        $model = trim((string)$this->request->post('model', 'nano-banana-2'));
        $allowedModels = ['nano-banana-2', 'nano-banana-fast'];
        if (!in_array($model, $allowedModels, true)) {
            return $this->error('model 参数无效，仅支持 nano-banana-2 或 nano-banana-fast');
        }

        // 生成参数固定：image_size 与 shot_progress 固定
        $imageSize = '2K';
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
                $categoryName = Db::name('entity_category')
                    ->where('id', $categoryId)
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->value('name');

                $title = (string)($categoryName ?? '');
                $title = $title !== ''
                    ? (function_exists('mb_substr') ? mb_substr($title, 0, 100, 'UTF-8') : substr($title, 0, 100))
                    : 'AI生成作品';

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
     * 生成最终实体渲染图（通过已有的 aimade_generated_image.id 获取实体渲染参数）
     * @return \think\Response
     */
    public function generateRenderImage()
    {
        $userId = $this->getCurrentUserId();
        $generatedImageId = (int)$this->request->post('image_id', (int)$this->request->post('id', 0));

        if ($generatedImageId <= 0) {
            return $this->error('参数不完整');
        }

        // 读取原图生成记录：用于拿到 image_url 以及关联的实体分类 category_id
        $generated = Db::name('generated_image')->alias('gi')
            ->join('order_corpus oc', 'oc.id = gi.corpus_id')
            ->join('entity_order o', 'o.id = oc.order_id')
            ->field('gi.id,gi.image_url,gi.render_query_id,gi.render_url,o.id as order_id,o.user_id,o.category_id')
            ->where('o.user_id', $userId)
            ->where('gi.id', $generatedImageId)
            ->where('gi.status', 1)
            ->whereNull('gi.deleted_at')
            ->whereNull('oc.deleted_at')
            ->whereNull('o.deleted_at')
            ->find();

        if (!$generated) {
            return $this->error('图片任务不存在');
        }

        $originalImageUrl = (string)($generated['image_url'] ?? '');
        if ($originalImageUrl === '') {
            return $this->error('原始图片尚未生成完成');
        }

        // 如果渲染结果已存在，则直接返回当前 render_query_id 供轮询
        if (!empty($generated['render_url'])) {
            return $this->success([
                'render_query_id' => (string)($generated['render_query_id'] ?? ''),
            ], '已存在渲染结果');
        }

        $config = Db::name('entity_render_config')
            ->where('category_id', (int)($generated['category_id'] ?? 0))
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->find();

        if (!$config) {
            return $this->error('当前实体缺少渲染配置');
        }

        $entityImageUrl = (string)($config['entity_image_url'] ?? '');
        $fixedPrompt = (string)($config['fixed_render_prompt'] ?? '');

        if ($entityImageUrl === '' || $fixedPrompt === '') {
            return $this->error('渲染配置不完整');
        }

        // Nano-Banana 渲染入参：urls[0]=实体图片（被渲染物体），urls[1]=用户设计图片
        $urls = [$entityImageUrl, $originalImageUrl];

        $aspectRatio = '3:4'; // 默认
        $imageSize = '2K'; // 默认
        $model = 'nano-banana-fast';
        $shotProgress = false;

        try {
            $result = $this->nanoBananaService->generateImage(
                $fixedPrompt,
                $aspectRatio,
                $imageSize,
                $model,
                $shotProgress,
                $urls
            );
        } catch (\Throwable $e) {
            $this->writeLog('generate_render_image', '调用生成渲染图服务失败：' . $e->getMessage(), $userId, 3);
            return $this->error('生成渲染图请求失败，请稍后重试');
        }

        $renderQueryId = (string)($result['data']['id'] ?? '');
        if ($renderQueryId === '') {
            return $this->error('生成渲染图请求失败，请稍后重试');
        }

        Db::startTrans();
        try {
            // 写入渲染任务 id，并清空旧渲染 url
            Db::name('generated_image')
                ->where('id', (int)$generated['id'])
                ->update([
                    'render_query_id' => $renderQueryId,
                    'render_url' => null,
                    'status' => 1,
                ]);

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            $this->writeLog('generate_render_image', '渲染图落库失败：' . $e->getMessage(), $userId, 3);
            return $this->error('生成渲染任务创建失败，请稍后重试');
        }

        $this->writeLog('generate_render_image', '用户发起生成渲染图成功', $userId);

        return $this->success([
            'render_query_id' => $renderQueryId,
        ], '实体渲染任务创建成功');
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
            ->field('gi.id as image_id,gi.query_id,gi.render_query_id,gi.status,gi.image_url,gi.render_url,o.user_id,o.category_id')
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
                    $q->where('gi.render_query_id', $renderQueryId);
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
            // render 轮询仅返回 render_url 缓存；若 render_url 为空则继续请求第三方
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

        // 按 taskId 加互斥锁（Redis）：避免定时任务与本接口同时请求第三方并重复返还 remaining_renders
        $lockKey = 'lock:generated_image_result_task:' . md5($taskId);
        $ttlSeconds = 300;
        $token = bin2hex(random_bytes(16));
        $redis = null;
        try {
            $redis = Cache::store('redis')->handler();
        } catch (\Throwable $e) {
            $redis = null;
        }

        if ($redis !== null) {
            $locked = true;
            try {
                if (is_object($redis) && get_class($redis) === 'Redis') {
                    $res = $redis->set($lockKey, $token, ['nx', 'ex' => $ttlSeconds]);
                } else {
                    // Predis：SET key value NX EX seconds
                    $res = $redis->set($lockKey, $token, 'NX', 'EX', $ttlSeconds);
                }

                $locked = ($res === true || $res === 'OK');
            } catch (\Throwable $e) {
                // 加锁失败时放行，避免任务永久卡死（可能会出现少量重复回查）
                $locked = true;
            }

            if (!$locked) {
                return $this->success([
                    'status' => 0,
                    'message' => '图片正在生成中'
                ], '查询图片生成结果成功');
            }
        }

        $sync = new GeneratedImageResultSyncService($this->nanoBananaService);
        $isRenderPoll = $renderQueryId !== '' && $queryId === '';
        try {
            $out = $sync->pollThirdPartyAndPersist($generated, $taskId, $isRenderPoll, [
                'operation_ip' => $this->request->ip(),
                'user_agent' => $this->request->header('user-agent', ''),
            ]);
        } finally {
            if ($redis !== null) {
                try {
                    $redis->del($lockKey);
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }

        if ($out['type'] === 'error') {
            return $this->error('查询图片生成结果失败，请稍后重试');
        }
        if ($out['type'] === 'running') {
            return $this->success([
                'status' => 0,
                'message' => (string)($out['message'] ?? '图片正在生成中')
            ], '查询图片生成结果成功');
        }
        if ($out['type'] === 'succeeded') {
            return $this->success([
                'status' => 1,
                'message' => '图片生成成功',
                'url' => (string)($out['url'] ?? ''),
                'image_id' => (int)$generated['image_id'],
            ], '查询图片生成结果成功');
        }
        if ($out['type'] === 'failed') {
            return $this->success([
                'status' => 0,
                'message' => '图片生成失败：' . (string)($out['message'] ?? '')
            ], '查询图片生成结果成功');
        }

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
        $imageIdB64 = trim((string)$this->request->post('imageId', ''));
        $page = (int)$this->request->post('page', 1);
        if ($page <= 0) {
            $page = 1;
        }
        $perPage = 10;
        $imagePk = 0;
        if ($imageIdB64 !== '') {
            $decoded = base64_decode($imageIdB64, true);
            if ($decoded !== false) {
                $decoded = trim((string)$decoded);
                if (ctype_digit($decoded)) {
                    $imagePk = (int)$decoded;
                }
            }
        }

        $field = 'cc.id as creative_id, cc.title, cc.description, cc.likes_count, cc.views_count, cc.is_public, oc.id as corpus_id, oc.prompt, gi.image_url, gi.render_url';

        // 如果传入 imageId，则把对应图片放在数组第一个
        $firstRow = null;
        if ($imagePk > 0) {
            $firstRow = Db::name('creative_community')->alias('cc')
                ->join('generated_image gi', 'gi.id = cc.image_id')
                ->join('order_corpus oc', 'oc.id = gi.corpus_id')
                ->field($field)
                ->where('cc.status', 1)
                ->whereNull('cc.deleted_at')
                ->where('gi.status', 1)
                ->whereNull('gi.deleted_at')
                ->where('oc.status', 1)
                ->whereNull('oc.deleted_at')
                ->where('cc.id', $imagePk)
                ->find();
        }

        // 其余图片：仍按 cc.id 倒序，同时排除已置顶那条，避免重复
        $listQuery = Db::name('creative_community')->alias('cc')
            ->join('generated_image gi', 'gi.id = cc.image_id')
            ->join('order_corpus oc', 'oc.id = gi.corpus_id')
            ->field($field)
            ->where('cc.status', 1)
            ->whereNull('cc.deleted_at')
            ->where('gi.status', 1)
            ->whereNull('gi.deleted_at')
            ->where('oc.status', 1)
            ->whereNull('oc.deleted_at');

        if ($imagePk > 0) {
            $listQuery->where('cc.id', '<>', $imagePk);
        }

        $normalTotal = (int)(clone $listQuery)->count();
        $total = $normalTotal + ((is_array($firstRow) && !empty($firstRow)) ? 1 : 0);
        $totalPage = $total > 0 ? (int)ceil($total / $perPage) : 0;

        $result = [];
        $hasFirst = is_array($firstRow) && !empty($firstRow);
        if ($hasFirst && $page === 1) {
            $result[] = $firstRow;
        }

        if ($hasFirst) {
            $offset = $page === 1 ? 0 : (($page - 1) * $perPage - 1);
            if ($offset < 0) {
                $offset = 0;
            }
            $limit = $page === 1 ? ($perPage - 1) : $perPage;
        } else {
            $offset = ($page - 1) * $perPage;
            $limit = $perPage;
        }

        $list = $listQuery
            ->order('cc.id', 'desc')
            ->limit($offset, $limit)
            ->select()
            ->toArray();

        foreach ($list as $row) {
            $result[] = $row;
        }

        return $this->success([
            'list' => $result,
            'total_page' => $totalPage,
            'current_page' => $page,
        ], '获取用户分享的创意图片成功');
    }
}

