<?php

namespace vartruexuan\excel\events;

use vartruexuan\excel\data\export\ExportConfig;
use vartruexuan\excel\data\export\Sheet;
use yii\base\Event;

/**
 * 导出页码之前
 */
class ExportSheetEvent extends Event
{
    /**
     * 导出配置
     *
     * @var ExportConfig
     */
    public ExportConfig $exportConfig;

    /**
     * 页码配置信息
     *
     * @var Sheet
     */
    public Sheet $sheet;

}