<?php

namespace vartruexuan\excel;

use vartruexuan\excel\events\ProgressEvent;
use yii\helpers\Json;

/**
 * 进度信息操作
 */
trait ProgressTrait
{
    /**
     * 进度信息失效时长(秒)
     *
     * @var float|int
     */
    public $progressExpireTime = 60 * 60;

    /**
     * 是否允许写入进度
     * 
     * @var bool 
     */
    public $isEnableProgress = true;

    /**
     * 是否允许推送信息
     * 
     * @var bool 
     */
    public $isEnablePushMessage = true;


    /**
     * 页码缓存key
     *          1.token
     *          2.sheetName
     */
    public const PROGRESS_KEY_SHEET = 'Excel:progress:%s:sheet:%s';

    /**
     * 缓存key
     *      1.token
     */
    public const PROGRESS_KEY_INFO = 'Excel:progress:%s';
    /**
     *  信息
     *   1.token
     */
    public const PROGRESS_KEY_MESSAGE = 'Excel:progress:%s:message';

    // 进度状态
    public const PROGRESS_STATUS_AWAIT = 1; // 待处理
    public const PROGRESS_STATUS_PROCESS = 2; // 处理中
    public const PROGRESS_STATUS_END = 3; // 处理完成
    public const PROGRESS_STATUS_FAIL = 4; // 处理失败

    /**
     * 初始化进度信息
     *
     * @param string $token
     * @param array $sheetList
     * @return void
     */
    public function initProgressInfo(string $token)
    {
        return $this->setProgressInfo($token, [], self::PROGRESS_STATUS_AWAIT);
    }

    /**
     * 设置进度信息
     *
     * @param string $token token
     * @param array|null $sheetList 页信息
     * @param bool $isAwait 是否在等待处理
     * @param bool|null $status 进度状态
     * @param array|null $data 数据
     * @return mixed
     */
    public function setProgressInfo(string $token, ?array $sheetList = null, ?int $status = self::PROGRESS_STATUS_PROCESS, ?array $data = null)
    {
        // 初始化进度信息
        $info = $this->getProgressInfo($token);
        $info = $info ?? [];
        $info['status'] = $status;
        if ($sheetList !== null) {
            $info['sheetList'] = $sheetList;
        }
        if ($data) {
            $info['data'] = $data;
        }
        $this->redis->set($this->getProgressCacheKeyByInfo($token), Json::encode($info));
        $this->resetProgressTime($token);
    }

    /**
     * 获取进度信息
     *
     * @param string $token
     * @return mixed|null
     */
    public function getProgressInfo(string $token)
    {
        $info = $this->redis->get($this->getProgressCacheKeyByInfo($token));
        return Json::decode($info, true);
    }

    /**
     * 查询进度
     *
     * @param $token
     * @return void
     */
    public function getProgress($token)
    {
        $info = $this->getProgressInfo($token);
        if (!$info) {
            return false;
        }
        $progressInfo = [
            'sheetList' => [],
            'all' => [
                'total' => 0,
                'progress' => 0,
                'success' => 0,
                'fail' => 0,
                'status' => (int)($info['status'] ?? self::PROGRESS_STATUS_AWAIT),
            ],
            'data' => $info['data'] ?? null,
        ];
        foreach ($info['sheetList'] ?? [] as $key => $sheetName) {
            $sheetProgress = $this->getSheetProgress($token, $sheetName);
            $progressInfo['sheetList'][$sheetName] = $sheetProgress;
            $progressInfo['all']['total'] += $sheetProgress['total'] ?? 0;
            $progressInfo['all']['progress'] += $sheetProgress['progress'] ?? 0;
            $progressInfo['all']['success'] += $sheetProgress['success'] ?? 0;
            $progressInfo['all']['fail'] += $sheetProgress['fail'] ?? 0;
        }


        return $progressInfo;
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
        $key = $this->getProgressCacheKeyBySheet($token, $sheetName);
        $this->redis->hmset($key, [
            'total' => $total,
            'progress' => 0,
            'success' => 0,
            'fail' => 0,
            'status' => self::PROGRESS_STATUS_AWAIT,
        ]);
        $this->resetProgressTime($token);

    }

    /**
     * 设置页码进度
     *
     * @param string $token
     * @param string $sheetName
     * @param bool $status
     * @param int $progress
     * @param int $success
     * @param int $fail
     * @return void
     */
    public function setSheetProgress(string $token, string $sheetName, ?int $status = null, int $progress = 0, int $success = 0, int $fail = 0)
    {
        $key = $this->getProgressCacheKeyBySheet($token, $sheetName);
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

        $progressInfo = $this->getProgressInfo($token);
        // 触发事件
        $this->trigger(ExcelAbstract::EVENT_AFTER_PROGRESS, new ProgressEvent([
            'progressInfo' => $progressInfo,
            'token' => $token,
        ]));
    }


    /**
     * 设置消息（队列方式）
     *
     * @return void
     */
    public function setProgressMessage($token, $message)
    {
        $key = $this->getProgressCacheKeyByMessage($token);
        $this->redis->lpush($key, $message);
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
        $key = $this->getProgressCacheKeyByMessage($token);
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
     * @param $key
     * @param $time
     * @return mixed
     */
    public function resetProgressTime($token, $time = null)
    {
        $time = $time ? $time : $this->progressExpireTime;
        $this->redis->expire($this->getProgressCacheKeyByInfo($token), $time);
        $this->redis->expire($this->getProgressCacheKeyByMessage($token), $time);
        $progressInfo = $this->getProgressInfo($token);

        foreach ($progressInfo['sheetList'] ?? [] as $sheetName) {
            $this->redis->expire($this->getProgressCacheKeyBySheet($token, $sheetName), $time);
        }

    }


    /**
     * 获取页码进度信息
     *
     * @param string $token
     * @param string $sheetName
     * @return mixed
     */
    public function getSheetProgress(string $token, string $sheetName)
    {
        $result = $this->redis->hgetall($this->getProgressCacheKeyBySheet($token, $sheetName));
        return [
            'total' => intval($result['total'] ?? 0),
            'progress' => intval($result['progress'] ?? 0),
            'success' => intval($result['success'] ?? 0),
            'fail' => intval($result['fail'] ?? 0),
            'status' => intval($result['status'] ?? self::PROGRESS_STATUS_AWAIT),
        ];
    }


    /**
     * 获取进度信息key
     *
     * @param $token
     * @return string
     */
    protected function getProgressCacheKeyByInfo($token)
    {
        return sprintf(self::PROGRESS_KEY_INFO, $token);
    }

    /**
     * 获取页码进度key
     *
     * @param $token
     * @return string
     */
    protected function getProgressCacheKeyBySheet($token, $sheetName)
    {
        return sprintf(self::PROGRESS_KEY_SHEET, $token, $sheetName);
    }

    /**
     * 错误信息
     *
     * @param $token
     * @return string
     */
    protected function getProgressCacheKeyByMessage($token)
    {
        return sprintf(self::PROGRESS_KEY_MESSAGE, $token);
    }

    /**
     * 计算状态
     *
     * @param $sheetStatusList
     * @return void
     */
    protected function getAllStatus($sheetStatusList)
    {
        $allStatus = self::PROGRESS_STATUS_AWAIT;
        $sheetStatusList = array_unique($sheetStatusList ?? []);
        $sheetStatusCount = count($sheetStatusList);
        if ($sheetStatusCount == 1) {
            $allStatus = $sheetStatusList[0];
        } else if ($sheetStatusCount > 1) {
            if (!in_array(self::PROGRESS_STATUS_FAIL, $sheetStatusList)) {
                $allStatus = self::PROGRESS_STATUS_FAIL;
            } else {
                $allStatus = self::PROGRESS_STATUS_PROCESS;
            }
        } else {
            $allStatus = self::PROGRESS_STATUS_AWAIT;
        }
        return $allStatus;
    }

}