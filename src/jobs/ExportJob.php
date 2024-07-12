<?php

namespace vartruexuan\excel\jobs;

use vartruexuan\excel\data\export\ExportConfig;
use vartruexuan\excel\jobs\base\BaseJob;
use yii\base\BaseObject;
use yii\db\Exception;
use yii\queue\JobInterface;
use vartruexuan\excel\Excel;

/**
 * 导出
 */
class ExportJob extends BaseJob
{
    /**
     * 导入配置
     *
     * @var ExportConfig
     */
    public ExportConfig $exportConfig;

    public function execute($queue)
    {
        try {
            $this->exportConfig->isAsync = false;
            $this->getExcelInstance()->export($this->exportConfig);
        } catch (\Throwable $exception) {
            \Yii::error($exception->getMessage(), 'vartruexuan/excel/ExportJob');
            throw $exception;
        }
    }
}