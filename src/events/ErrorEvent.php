<?php

namespace vartruexuan\excel\events;

use vartruexuan\excel\data\export\ExportConfig;
use vartruexuan\excel\data\import\ImportConfig;
use yii\base\Event;

/**
 * 错误事件
 */
class ErrorEvent extends Event
{

    /**
     * 导入导出配置
     *
     * @var ImportConfig|ExportConfig
     */
    public $config;

    /**
     * 异常信息
     *
     * @var
     */
    public $exception;

}