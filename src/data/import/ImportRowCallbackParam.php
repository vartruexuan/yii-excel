<?php

namespace vartruexuan\excel\data\import;

use vartruexuan\excel\ExcelAbstract;
use yii\base\BaseObject;

class ImportRowCallbackParam extends BaseObject
{

    public ExcelAbstract $excel;

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

    /**
     * 数据行
     *
     * @var array
     */
    public array $row;

}