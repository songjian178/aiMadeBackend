<?php
declare(strict_types=1);

namespace app\command;

use app\service\GeneratedImageResultSyncService;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;
use think\facade\Cache;

class PollGeneratedImageResult extends Command
{
    private const LOCK_FILENAME = 'poll_generated_image_result.lock';
    private const TASK_LOCK_PREFIX = 'lock:generated_image_result_task:';
    private const TASK_LOCK_TTL_SECONDS = 300;

    protected function configure(): void
    {
        $this->setName('poll:generated-image-result')
            ->setDescription('轮询未完成的设计图/渲染图任务，同步 NanoBanana 结果到数据库（单实例文件锁，防 crontab 重复拉起）')
            ->addOption('daemon', 'd', Option::VALUE_NONE, '守护模式：按间隔重复执行（默认每 1 分钟一轮）')
            ->addOption('sleep', null, Option::VALUE_OPTIONAL, '守护模式下休眠秒数（默认 60，即 1 分钟）', '60');
    }

    protected function execute(Input $input, Output $output): int
    {
        $lockPath = $this->app->getRuntimePath() . self::LOCK_FILENAME;
        $fp = @fopen($lockPath, 'c+');
        if ($fp === false) {
            $output->writeln('<error>无法打开锁文件：' . $lockPath . '</error>');

            return 1;
        }

        // 非阻塞独占锁：已有实例（守护进程或单次任务未跑完）在跑则直接退出，供 crontab 探活时不重复启动
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            $output->writeln('<comment>poll:generated-image-result 已在运行，跳过本次</comment>');

            return 0;
        }

        try {
            $daemon = (bool)$input->getOption('daemon');
            $sleep = (int)$input->getOption('sleep');
            if ($sleep < 1) {
                $sleep = 60;
            }

            $service = new GeneratedImageResultSyncService();

            do {
                $this->runOnce($service, $output);
                if ($daemon) {
                    sleep($sleep);
                }
            } while ($daemon);
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        return 0;
    }

    private function runOnce(GeneratedImageResultSyncService $service, Output $output): void
    {
        $rows = Db::name('generated_image')->alias('gi')
            ->join('order_corpus oc', 'oc.id = gi.corpus_id')
            ->join('entity_order o', 'o.id = oc.order_id')
            ->field('gi.id as image_id,gi.query_id,gi.render_query_id,gi.image_url,gi.render_url,o.user_id,o.category_id')
            ->where('gi.status', 1)
            ->whereNull('gi.deleted_at')
            ->where('oc.status', 1)
            ->whereNull('oc.deleted_at')
            ->whereNull('o.deleted_at')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereRaw("COALESCE(NULLIF(TRIM(gi.query_id), ''), '') <> ''")
                        ->where(function ($q3) {
                            $q3->whereNull('gi.image_url')->whereOr('gi.image_url', '=', '');
                        });
                })->whereOr(function ($q2) {
                    $q2->whereRaw("COALESCE(NULLIF(TRIM(gi.render_query_id), ''), '') <> ''")
                        ->where(function ($q3) {
                            $q3->whereNull('gi.render_url')->whereOr('gi.render_url', '=', '');
                        });
                });
            })
            ->order('gi.id', 'asc')
            ->limit(80)
            ->select()
            ->toArray();

        if ($rows === []) {
            return;
        }

        $redis = null;
        try {
            $redis = Cache::store('redis')->handler();
        } catch (\Throwable $e) {
            // Redis 不可用时仍放行任务，避免队列永久卡死（可能会出现少量重复回查）
            $redis = null;
        }

        foreach ($rows as $row) {
            $qid = trim((string)($row['query_id'] ?? ''));
            $imgUrl = trim((string)($row['image_url'] ?? ''));
            $rqid = trim((string)($row['render_query_id'] ?? ''));
            $rUrl = isset($row['render_url']) ? trim((string)$row['render_url']) : '';

            $taskId = '';
            $isRenderPoll = false;
            if ($qid !== '' && $imgUrl === '') {
                $taskId = $qid;
                $isRenderPoll = false;
            } elseif ($rqid !== '' && $rUrl === '') {
                $taskId = $rqid;
                $isRenderPoll = true;
            } else {
                continue;
            }

            $generated = [
                'image_id' => (int)$row['image_id'],
                'user_id' => (int)$row['user_id'],
                'category_id' => (int)$row['category_id'],
            ];

            // 按 taskId 加互斥锁（Redis）：避免定时任务与接口同时处理同一个 query_id/render_query_id
            $lockKey = self::TASK_LOCK_PREFIX . md5($taskId);
            $token = bin2hex(random_bytes(16));
            $locked = true;

            if ($redis !== null) {
                try {
                    if (is_object($redis) && get_class($redis) === 'Redis') {
                        $res = $redis->set($lockKey, $token, ['nx', 'ex' => self::TASK_LOCK_TTL_SECONDS]);
                    } else {
                        // Predis：SET key value NX EX seconds
                        $res = $redis->set($lockKey, $token, 'NX', 'EX', self::TASK_LOCK_TTL_SECONDS);
                    }

                    $locked = ($res === true || $res === 'OK');
                } catch (\Throwable $e) {
                    // 加锁失败时放行，避免任务堆积
                    $locked = true;
                }
            }

            if (!$locked) {
                continue;
            }

            try {
                $out = $service->pollThirdPartyAndPersist($generated, $taskId, $isRenderPoll);
                if ($out['type'] === 'error') {
                    $output->writeln(sprintf(
                        '[poll] image_id=%d task=%s error=%s',
                        $generated['image_id'],
                        $taskId,
                        (string)($out['message'] ?? '')
                    ));
                }
            } finally {
                if ($redis !== null) {
                    try {
                        $redis->del($lockKey);
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }
            }
        }
    }
}
