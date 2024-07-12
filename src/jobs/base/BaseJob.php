<?php

namespace vartruexuan\excel\jobs\base;

use vartruexuan\excel\Excel;
use vartruexuan\excel\ExcelAbstract;
use vartruexuan\excel\ExcelManager;
use vartruexuan\excel\exceptions\ExcelException;
use yii\base\BaseObject;
use yii\base\Component;
use yii\queue\JobInterface;

abstract class BaseJob extends BaseObject implements JobInterface
{

    public string $componentId = '';

    /**
     * 获取组件实例
     *
     * @return ExcelAbstract
     */
    protected function getExcelInstance()
    {
        return ExcelManager::instance()->getExcelInstance($this->componentId);
    }

}