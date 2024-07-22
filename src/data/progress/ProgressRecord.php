<?php

namespace vartruexuan\excel\data\progress;

use yii\base\ArrayableTrait;
use yii\base\BaseObject;


/**
 * 进度信息
 */
class ProgressRecord extends BaseObject
{
    use ArrayableTrait;

    public $token;

    /**
     * 页码信息
     *
     * @var array|null
     */
    public ?array $sheetList = null;

    /**
     * 页码进度信息
     *
     * @var ProgressData[]|null
     */
    public ?array $sheetListProgress = null;

    /**
     * 进度信息
     *
     * @var ProgressData|null
     */
    public ?ProgressData $progress = null;

    /**
     * 数据
     *
     * @var
     */
    public $data;


}