<?php

namespace vartruexuan\excel;

use vartruexuan\excel\data\progress\ProgressData;
use vartruexuan\excel\data\progress\ProgressRecord;
use yii\base\StaticInstanceTrait;
use vartruexuan\excel\events\ProgressEvent;
use yii\base\Component;
use yii\di\Instance;
use yii\queue\Queue;
use yii\redis\Connection;

class ExcelProgress extends Component
{
    use StaticInstanceTrait;

    /**
     * 前缀
     *
     * @var
     */
    public $prefix = 'Excel';

    /**
     * redis实例
     *
     * @var Connection
     */
    public $redis = 'redis';

    /**
     * 队列实例
     *
     * @var \yii\queue\Queue
     */
    public $queue = 'queue';

    /**
     * 进度信息失效时长(秒)
     *
     * @var float|int
     */
    public $expireTime = 60 * 60;

    /**
     * 进度状态
     */
    public const PROGRESS_STATUS_AWAIT = 1; // 待处理
    public const PROGRESS_STATUS_PROCESS = 2; // 处理中
    public const PROGRESS_STATUS_END = 3; // 处理完成
    public const PROGRESS_STATUS_FAIL = 4; // 处理失败


    public function init()
    {
        if (!$this->redis instanceof Connection) {
            $this->redis = Instance::ensure($this->redis, '\yii\redis\Connection');
        }

        if (!$this->queue instanceof Queue) {
            $this->queue = Instance::ensure($this->queue, '\yii\queue\Queue');
        }

        parent::init();
    }


    /**
     * 初始化进度记录
     *
     * @param string $token
     * @param array $sheetList
     * @return void
     */
    public function initProgressRecord(string $token)
    {
        return $this->setProgressRecord($token, [], self::PROGRESS_STATUS_AWAIT);
    }

    /**
     * 设置进度记录
     *
     * @param string $token
     * @param array|null $sheetList
     * @param int|null $status
     * @param array|null $data
     * @return void
     */
    public function setProgressRecord(string $token, ?array $sheetList = null, ?int $status = self::PROGRESS_STATUS_PROCESS, ?array $data = null)
    {
        $progressRecord = $this->getProgressRecord($token);
        $progressRecord = $progressRecord ?: new ProgressRecord([
            'token' => $token,
        ]);


        $progressRecord->progress->status = $status;

        if ($sheetList !== null) {
            $progressRecord->sheetList = $sheetList;
        }
        if ($data) {
            $progressRecord->data = $data;
        }

        $this->redis->set($this->getKeyByProgressRecord($token), $this->serializeProgressRecord($progressRecord));

        $this->resetProgressTime($token);

        return $this;
    }

    /**
     * 获取进度信息
     *
     * @param string $token
     * @return ProgressRecord|null
     */
    public function getProgressRecord(string $token, $isGetProgress = false)
    {
        $result = $this->redis->get($this->getKeyByProgressRecord($token));
        if (!$result) {
            return null;
        }

        $progressRecord = $this->unserializeProgressRecord($result);

        if ($isGetProgress) {
            // 设置进度
            foreach ($progressRecord->sheetList ?? [] as $sheetName) {
                $progressRecord->sheetListProgress[$sheetName] = $sheetProgress = $this->getSheetProgress($token, $sheetName);
                $progressRecord->progress->total += $sheetProgress->total ?? 0;
                $progressRecord->progress->progress += $sheetProgress->progress ?? 0;
                $progressRecord->progress->success += $sheetProgress->success ?? 0;
                $progressRecord->progress->fail += $sheetProgress->fail ?? 0;
            }
        }

        return $progressRecord;
    }


    /**
     * 初始化进度信息
     *
     * @param string $token
     * @param string $sheetName
     * @param int $total
     * @return void
     */
    public function initSheetProgress(string $token, string $sheetName, int $total = 0)
    {
        $key = $this->getKeyByProgressSheet($token, $sheetName);
        if (!$this->redis->exists($key)) {
            $this->redis->hmset($key, (new ProgressData([
                'total' => $total,
                'progress' => 0,
                'success' => 0,
                'fail' => 0,
                'status' => self::PROGRESS_STATUS_AWAIT,
            ]))->toArray());
        }
        $this->resetProgressTime($token);
    }

    /**
     * 设置页码进度
     *
     * @param string $token
     * @param string $sheetName
     * @param int|null $status
     * @param int $progress
     * @param int $success
     * @param int $fail
     * @return void
     */
    public function setSheetProgress(string $token, string $sheetName, ?int $status = null, $total = 0, int $progress = 0, int $success = 0, int $fail = 0)
    {
        $key = $this->getKeyByProgressSheet($token, $sheetName);

        $this->initSheetProgress($token, $sheetName, 0);

        if ($total > 0) {
            $this->redis->hincrby($key, 'total', $total);
        }
        if ($progress > 0) {
            $this->redis->hincrby($key, 'progress', $progress);
        }
        if ($success > 0) {
            $this->redis->hincrby($key, 'success', $success);
        }
        if ($fail > 0) {
            $this->redis->hincrby($key, 'fail', $fail);
        }
        if ($status !== null) {
            $this->redis->hmset($key, 'status', $status);
        }

        $this->resetProgressTime($token);
    }


    /**
     * 获取页码进度信息
     *
     * @param string $token
     * @param string $sheetName
     * @return ProgressData
     */
    public function getSheetProgress(string $token, string $sheetName): ProgressData
    {
        $result = $this->redis->hgetall($this->getKeyByProgressSheet($token, $sheetName));
        return new ProgressData([
            'total' => intval($result['total'] ?? 0),
            'progress' => intval($result['progress'] ?? 0),
            'success' => intval($result['success'] ?? 0),
            'fail' => intval($result['fail'] ?? 0),
            'status' => intval($result['status'] ?? self::PROGRESS_STATUS_AWAIT),
        ]);
    }


    /**
     * 设置消息（队列方式）
     *
     * @return void
     */
    public function pushProgressMessage($token, $message)
    {
        $this->redis->lpush($this->getKeyByProgressMessage($token), $message);

        $this->resetProgressTime($token);
    }

    /**
     * 获取进度消息
     *
     * @param $token
     * @param int $limit 限制数量
     * @return array
     */
    public function getProgressMessage($token, int $limit = 30)
    {
        $key = $this->getKeyByProgressMessage($token);
        $messages = [];
        for ($i = 0; $i < $limit; $i++) {
            if ($message = $this->redis->rpop($key)) {
                $messages[] = $message;
            }
        }
        return $messages;
    }


    /**
     * 重置key时间
     *
     * @param $token
     * @param null $time
     * @return mixed
     */
    protected function resetProgressTime($token, $time = null)
    {
        $time = $time > 0 ? $time : $this->getExpireTime();
        foreach ($this->getAllKeyByProgress($token) as $key) {
            $this->redis->expire($key, $time);
        }
    }

    /**
     * 失效时长
     *
     * @return float|int
     */
    protected function getExpireTime()
    {
        return $this->expireTime;
    }

    /**
     * 整体进度信息（缓存key）
     *
     * @param $token
     * @return string
     */
    protected function getKeyByProgressRecord($token): string
    {
        return sprintf('%s:progress:%s', $this->getPrefix(), $token);
    }

    /**
     * 页码进度（缓存key）
     *
     * @param $token
     * @param $sheetName
     * @return string
     */
    protected function getKeyByProgressSheet($token, $sheetName): string
    {
        return sprintf('%s:progress:%s:sheet:%s', $this->getPrefix(), $token, $sheetName);
    }

    /**
     * 进度消息 (缓存key)
     *
     * @param $token
     * @return string
     */
    protected function getKeyByProgressMessage($token): string
    {
        return sprintf('%s:progress:%s:message', $this->getPrefix(), $token);
    }

    /**
     * 获取所有缓存key
     *
     * @return array|string[]
     */
    protected function getAllKeyByProgress($token)
    {
        $progressRecord = $this->getProgressRecord($token);
        return array_merge([
            $this->getKeyByProgressRecord($token),
            $this->getKeyByProgressMessage($token),
        ],
            // 页码key
            array_map(function ($sheetName) use ($token) {
                return $this->getKeyByProgressSheet($token, $sheetName);
            }, $progressRecord ? $progressRecord->sheetList : []));
    }


    /**
     * 获取前缀
     *
     * @return void
     */
    protected function getPrefix()
    {
        return $this->prefix ?: 'Excel';
    }


    /**
     * 序列化进度记录
     *
     * @param ProgressRecord $progressRecord
     * @return string
     */
    public function serializeProgressRecord(ProgressRecord $progressRecord)
    {
        return serialize($progressRecord);
    }


    /**
     * 反序列化进度信息
     *
     * @param string $recordString
     * @return ProgressRecord|null
     */
    public function unserializeProgressRecord(string $recordString)
    {
        return unserialize($recordString);
    }

}