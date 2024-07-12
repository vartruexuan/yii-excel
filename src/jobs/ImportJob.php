<?php

namespace vartruexuan\excel\jobs;

use vartruexuan\excel\data\import\ImportConfig;
use vartruexuan\excel\Excel;
use vartruexuan\excel\jobs\base\BaseJob;
use yii\base\BaseObject;
use yii\db\Exception;
use yii\queue\JobInterface;
use yii\queue\Queue;

/**
 * 导入
 */
class ImportJob extends BaseJob
{
    /**
     * 导入配置
     *
     * @var ImportConfig
     */
    public ImportConfig $importConfig;

    public function execute($queue)
    {
        try {
            $this->importConfig->isAsync = false;
            $this->getExcelInstance()->import($this->importConfig);
        } catch (\Throwable $exception) {
            \Yii::error($exception->getMessage(), 'vartruexuan/excel/ImportJob');
            throw $exception;
        }

    }
}