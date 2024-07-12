<?php

namespace vartruexuan\excel\data\export;

use yii\base\BaseObject;

/**
 * 列配置
 */
class Column extends BaseObject
{

    /**
     * 列标识
     *
     * @var string
     */
    public string $key;

    /**
     * 列标题
     *
     * @var string
     */
    public string $title;

    /**
     * 数据类型
     *
     * @var string
     */
    public string $type;
    /**
     * 字段名
     *
     * @var string
     */
    public string $field;

    /**
     * 数据回调
     *
     * @var Callback
     *
     * `
     *      function($row){
     *          return $row['title'];
     *      }
     * `
     *
     */
    public $callback = null;

    /**
     * 设置列宽
     *
     * @var int
     */
    public int $width = 0;

    /**
     * 额外配置
     *
     * @var
     */
    public array $options = [];


}