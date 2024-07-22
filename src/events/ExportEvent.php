<?php

namespace vartruexuan\excel\events;

use vartruexuan\excel\data\export\ExportConfig;
use vartruexuan\excel\data\export\ExportData;
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

    /**
     * 导出数据
     *
     * @var ExportData
     */
    public ExportData $exportData;

}