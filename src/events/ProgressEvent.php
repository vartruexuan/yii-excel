<?php

namespace vartruexuan\excel\events;

use yii\base\Event;

class ProgressEvent extends Event
{
    /**
     * 进度信息
     *
     * @var array|null
     */
    public ?array $progressInfo = null;
    /**
     * token
     *
     * @var string
     */
    public string $token = '';
}