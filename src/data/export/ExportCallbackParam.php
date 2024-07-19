<?php

namespace vartruexuan\excel\data\export;

use vartruexuan\excel\ExcelAbstract;
use yii\base\BaseObject;

class ExportCallbackParam extends BaseObject
{

    /**
     * 当前excel组件
     * 
     * @var ExcelAbstract 
     */
    public ExcelAbstract $excel;
    
    /**
     * 导出配置信息
     *
     * @var ExportConfig
     */
    public ExportConfig $exportConfig;

    /**
     * 当前页码信息
     *
     * @var Sheet
     */
    public Sheet $sheet;

    /**
     * 当前分页
     *
     * @var int
     */
    public int $page = 1;

    /**
     * 当前分页限制数量
     *
     * @var int
     */
    public int $pageSize = 10;

    /**
     * 数据总数
     *
     * @var int
     */
    public int $totalCount = 0;

}