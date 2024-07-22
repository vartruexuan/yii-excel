<?php

namespace vartruexuan\excel\events;

use vartruexuan\excel\data\import\ImportConfig;
use vartruexuan\excel\data\import\Sheet;
use yii\base\Event;

class ImportSheetEvent extends Event
{
    /**
     * 导入配置
     *
     * @var ImportConfig
     */
    public ImportConfig $importConfig;

    /**
     * 页码信息
     *
     * @var Sheet
     */
    public Sheet $sheet;


}