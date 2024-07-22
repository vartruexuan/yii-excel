<?php

namespace vartruexuan\excel\events;

use vartruexuan\excel\data\export\ExportCallbackParam;
use vartruexuan\excel\data\export\ExportConfig;
use vartruexuan\excel\data\export\Sheet;
use yii\base\Event;


/**
 * 导出数据事件
 */
class ExportDataEvent extends Event
{

    /**
     * 导出配置
     *
     * @var ExportCallbackParam
     */
    public ExportCallbackParam $exportCallbackParam;

    /**
     * 数据
     *
     * @var array
     */
    public array $data = [];


}