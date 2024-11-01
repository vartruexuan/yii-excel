<?php

namespace vartruexuan\excel\events;

use vartruexuan\excel\data\export\ExportConfig;
use yii\base\Event;

/**
 * 导出输出文件事件
 */
class ExportOutputEvent extends Event
{
    /**
     * 导出配置
     *
     * @var ExportConfig
     */
    public ExportConfig $exportConfig;
    
}