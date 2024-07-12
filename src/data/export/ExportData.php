<?php

namespace vartruexuan\excel\data\export;

use yii\base\BaseObject;

class ExportData extends BaseObject
{
    /**
     * 导出token
     *
     * @var string
     */
    public string $token = '';

    /**
     * 导出文件地址
     *
     * @var string
     */
    public string $path = '';


    /**
     * 队列ID
     *
     * @var mixed
     */
    public $queueId;


    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }


    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }


    public function setQueueId($queueId)
    {
        $this->queueId = $queueId;
        return $this;
    }

}