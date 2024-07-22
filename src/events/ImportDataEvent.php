<?php

namespace vartruexuan\excel\events;

use vartruexuan\excel\data\import\ImportConfig;
use vartruexuan\excel\data\import\ImportRowCallbackParam;
use vartruexuan\excel\data\import\Sheet;
use yii\base\Event;


class ImportDataEvent extends Event
{

    public ImportRowCallbackParam $importRowCallbackParam;

    /**
     * 行数据
     *
     * @var
     */
    public $row;

    /**
     * 是否导入成功
     *
     * @var bool
     */
    public $isSuccess = true;
}