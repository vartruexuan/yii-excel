<?php

namespace vartruexuan\excel;

use vartruexuan\excel\exceptions\ExcelException;
use yii\base\BaseObject;
use yii\base\StaticInstanceTrait;

class ExcelManager extends BaseObject
{
    use StaticInstanceTrait;


    /**
     * 获取操作实例
     *
     * @param string|null $componentId
     * @param string $defaultDriverClass
     * @return mixed|object|null
     * @throws ExcelException
     */
    public function getExcelInstance(?string $componentId = null, string $defaultDriverClass = '\vartruexuan\excel\drivers\xlswriter\Excel')
    {
        if ($componentId) {
            if (!\Yii::$app->has($componentId)) {
                throw new ExcelException("component id  error {$this->componentId}");
            }
            $component = \Yii::$app->{$componentId};
            if (!$component instanceof ExcelAbstract) {
                throw new ExcelException("component is not ExcelAbstract instance");
            }
            return $component;
        }
        $defaultDriverClass = $defaultDriverClass ?: $this->getDefaultDriverClass();
        return $defaultDriverClass::instance();
    }

    /**
     * 获取默认驱动
     *
     * @return string
     */
    public function getDefaultDriverClass()
    {
        return '\vartruexuan\excel\drivers\xlswriter\Excel';
    }

}