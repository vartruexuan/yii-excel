<?php

namespace vartruexuan\excel\events;

use vartruexuan\excel\data\import\ImportConfig;
use yii\base\Event;

class ImportEvent extends Event
{

    /**
     * 导入配置
     *
     * @var ImportConfig
     */
    public ImportConfig $importConfig;

    public array $sheetNames = [];

}