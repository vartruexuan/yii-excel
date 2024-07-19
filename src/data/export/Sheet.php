<?php

namespace vartruexuan\excel\data\export;

use vartruexuan\excel\data\export\Column;
use yii\base\BaseObject;

/**
 * excel页
 */
class Sheet extends BaseObject
{

    /**
     * sheet名称
     *
     * @var string
     */
    public string $name = 'sheet1';

    /**
     * 列头配置
     *
     * @var \vartruexuan\excel\data\export\Column[]
     */
    public array $columns;

    /**
     * 数据总数量
     *
     * @var int
     */
    public int $count = 0;

    /**
     * 分页导出时/每页的数据量
     *
     * @var int
     */
    public int $pageSize = 2000;

    /**
     * 数据
     *  1. 数据回调
     *   `
     *      function(ExportCallbackParam $callbackParam){
     *         // 执行业务数据查询
     *     }
     * `
     *  2. 数据
     *
     * @var callable|array
     */
    public $data;

    /**
     * 额外配置
     *
     * @var
     */
    public array $options = [];


    /**
     * 获取列配置
     *
     * @return Column[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * 获取页码名
     *
     * @return void
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * 获取数据
     *
     * @return void
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 获取数据数量
     *
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * 获取每页导出数量
     *
     * @return int
     */
    public function getPageSize()
    {
        return $this->pageSize;

    }

    /**
     * 获取头部信息
     *
     * @return array|string[]
     */
    public function getHeaders()
    {
        return array_map(function (Column $col) {
            return $col->title;
        }, $this->getColumns());
    }


    /**
     * 格式行数据
     *
     * @param $row
     * @return array
     */
    public function formatRow($row)
    {
        $newRow = [];
        foreach ($this->columns as $column) {
            $newRow[$column->field] = $row[$column->field] ?? '';
            if (is_callable($column->callback)) {
                $newRow[$column->field] = call_user_func($column->callback, $row);
            }
        }
        return $newRow;
    }

    /**
     * 格式化多行数据
     *
     * @param $list
     * @return array
     */
    public function formatList($list)
    {
        return array_map([$this, 'formatRow'], $list ?? []);
    }
}