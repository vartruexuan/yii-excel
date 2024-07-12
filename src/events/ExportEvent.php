<?php

namespace vartruexuan\excel\events;

use vartruexuan\excel\data\export\ExportConfig;
use yii\base\Event;
/**
 * 导出事件
 */
class ExportEvent extends Event
{

    /**
     * 导出配置
     *
     * @var ExportConfig
     */
    public ExportConfig $exportConfig;

}