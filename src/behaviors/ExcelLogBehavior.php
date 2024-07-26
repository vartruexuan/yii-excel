<?php

namespace vartruexuan\excel\behaviors;

use vartruexuan\excel\data\progress\ProgressData;
use vartruexuan\excel\events\ErrorEvent;
use vartruexuan\excel\events\ExportDataEvent;
use vartruexuan\excel\events\ExportEvent;
use vartruexuan\excel\events\ExportSheetEvent;
use vartruexuan\excel\events\ImportDataEvent;
use vartruexuan\excel\events\ImportEvent;
use vartruexuan\excel\events\ImportSheetEvent;
use vartruexuan\excel\ExcelAbstract;
use vartruexuan\excel\ExcelProgress;
use yii\base\Behavior;
use yii\base\Event;
use yii\helpers\VarDumper;

/**
 * 进度行为
 */
class ExcelLogBehavior extends Behavior
{

    public function events()
    {
        return [
            // 导出
            ExcelAbstract::ENVENT_BEFORE_EXPORT => 'beforeExport',
            ExcelAbstract::ENVENT_AFTER_EXPORT => 'afterExport',
            ExcelAbstract::EVENT_BEFORE_EXPORT_EXCEL => 'beforeExportExcel',
            ExcelAbstract::EVENT_AFTER_EXPORT_EXCEL => 'afterExportExcel',
            ExcelAbstract::EVENT_BEFORE_EXPORT_SHEET => 'beforeExportSheet',
            ExcelAbstract::EVENT_AFTER_EXPORT_SHEET => 'afterExportSheet',
            ExcelAbstract::EVENT_BEFORE_EXPORT_DATA => 'beforeExportData',
            ExcelAbstract::EVENT_AFTER_EXPORT_DATA => 'afterExportData',

            // 导入
            ExcelAbstract::ENVENT_AFTER_IMPORT => 'afterImport',
            ExcelAbstract::ENVENT_BEFORE_IMPORT => 'beforeImport',
            ExcelAbstract::EVENT_BEFORE_IMPORT_EXCEL => 'beforeImportExcel',
            ExcelAbstract::EVENT_AFTER_IMPORT_EXCEL => 'afterImportExcel',
            ExcelAbstract::EVENT_BEFORE_IMPORT_SHEET => 'beforeImportSheet',
            ExcelAbstract::EVENT_AFTER_IMPORT_SHEET => 'afterImportSheet',
            ExcelAbstract::EVENT_BEFORE_IMPORT_DATA => 'beforeImportData',
            ExcelAbstract::EVENT_AFTER_IMPORT_DATA => 'afterImportData',

            // 错误
            ExcelAbstract::EVENT_ERROR => 'error',

        ];
    }

    /**
     * 导出之前
     *
     * @param ExportEvent $event
     * @return void
     */
    public function beforeExport(ExportEvent $event)
    {
        \Yii::info(sprintf('Export started,token:%s', $event->exportConfig->getToken()), $this->getCategory($event));
    }

    /**
     * 导出之后
     *
     * @param ExportEvent $event
     * @return void
     */
    public function afterExport(ExportEvent $event)
    {
        \Yii::info(sprintf('Export completed,token:%s,path:%s', $event->exportConfig->getToken(), $event->exportData->path), $this->getCategory($event));
    }

    /**
     * 执行导出之前
     *
     * @param ExportEvent $event
     * @return void
     */
    public function beforeExportExcel(ExportEvent $event)
    {
        \Yii::info(sprintf('Exporting in progress, token:%s,sheetList:%s', $event->exportConfig->getToken(), implode(',', $event->exportConfig->getSheetNames())), $this->getCategory($event));
    }


    /**
     * 执行导出之后
     *
     * @param ExportEvent $event
     * @return void
     */
    public function afterExportExcel(ExportEvent $event)
    {
        \Yii::info(sprintf('Export progress completed, token:%s,sheetList:%s', $event->exportConfig->getToken(), implode(',', $event->exportConfig->getSheetNames())), $this->getCategory($event));
    }

    /**
     * 导出sheet之前
     *
     * @param ExportSheetEvent $event
     * @return void
     */
    public function beforeExportSheet(ExportSheetEvent $event)
    {
        \Yii::info(sprintf('Export sheet started, token:%s,sheetName:%s', $event->exportConfig->getToken(), $event->sheet->getName()), $this->getCategory($event));
    }


    /**
     * 导出sheet之后
     *
     * @param ExportSheetEvent $event
     * @return void
     */
    public function afterExportSheet(ExportSheetEvent $event)
    {
        \Yii::info(sprintf('Export sheet completed, token:%s,sheetName:%s', $event->exportConfig->getToken(), $event->sheet->getName()), $this->getCategory($event));
    }


    /**
     * 导出数据之前
     *
     * @param ExportDataEvent $event
     * @return void
     */
    public function beforeExportData(ExportDataEvent $event)
    {
        \Yii::info(
            sprintf(
                'Export data , token:%s,sheetName:%s, totalCount:%d, pageSize:%d, page:%d',
                $event->exportCallbackParam->exportConfig->getToken(),
                $event->exportCallbackParam->sheet->getName(),
                $event->exportCallbackParam->totalCount,
                $event->exportCallbackParam->pageSize,
                $event->exportCallbackParam->page,
            ),
            $this->getCategory($event)
        );

    }

    /**
     * 导出数据之后
     *
     * @param ExportDataEvent $event
     * @return void
     */
    public function afterExportData(ExportDataEvent $event)
    {
        $token = $event->exportCallbackParam->exportConfig->getToken();
        $listCount = count($event->list);

        \Yii::info(
            sprintf(
                'Export data completed, token:%s,sheetName:%s, totalCount:%d, pageSize:%d, page:%d, dataCount:%d',
                $event->exportCallbackParam->exportConfig->getToken(),
                $event->exportCallbackParam->sheet->getName(),
                $event->exportCallbackParam->totalCount,
                $event->exportCallbackParam->pageSize,
                $event->exportCallbackParam->page,
                $listCount
            ),
            $this->getCategory($event)
        );

    }

    /**
     * 导入之前
     *
     * @param ImportEvent $event
     * @return void
     */
    public function beforeImport(ImportEvent $event)
    {
        \Yii::info(sprintf('Import started,token:%s,path:%s', $event->importConfig->getToken(), $event->importConfig->getPath()), $this->getCategory($event));
    }

    /**
     * 导入之后
     *
     * @param ImportEvent $event
     * @return void
     */
    public function afterImport(ImportEvent $event)
    {
        \Yii::info(sprintf('Import completed,token:%s', $event->importConfig->getToken()), $this->getCategory($event));
    }

    /**
     * 执行导出之前
     *
     * @param ImportEvent $event
     * @return void
     */
    public function beforeImportExcel(ImportEvent $event)
    {
        \Yii::info(sprintf('Importing in progress, token:%s,sheetList:%s', $event->importConfig->getToken(), implode(',', array_map('strtolower', $event->sheetNames))), $this->getCategory($event));
    }

    /**
     * 执行导出之后
     *
     * @param ImportEvent $event
     * @return void
     */
    public function afterImportExcel(ImportEvent $event)
    {
        \Yii::info(sprintf('Importing progress completed, token:%s,sheetList:%s', $event->importConfig->getToken(), implode(',', array_map('strtolower', $event->sheetNames))), $this->getCategory($event));
    }


    /**
     * 导入sheet之前
     *
     * @param ImportSheetEvent $event
     * @return void
     */
    public function beforeImportSheet(ImportSheetEvent $event)
    {
        \Yii::info(sprintf('Import sheet started, token:%s,sheetName:%s', $event->importConfig->getToken(), $event->sheet->getName()), $this->getCategory($event));
    }

    /**
     * 导入sheet之后
     *
     * @param ImportSheetEvent $event
     * @return void
     */
    public function afterImportSheet(ImportSheetEvent $event)
    {
        \Yii::info(sprintf('Export sheet completed, token:%s,sheetName:%s', $event->importConfig->getToken(), $event->sheet->getName()), $this->getCategory($event));
    }


    /**
     * 导入数据之前
     *
     * @param ImportDataEvent $event
     * @return void
     */
    public function beforeImportData(ImportDataEvent $event)
    {
        \Yii::info(
            sprintf(
                'Export data , token:%s,sheetName:%s, row:%s',
                $event->importRowCallbackParam->importConfig->getToken(),
                $event->importRowCallbackParam->sheet->getName(),
                var_export($event->row, true),
            ),
            $this->getCategory($event)
        );

    }

    /**
     * 导入数据之后
     *
     * @param ImportDataEvent $event
     * @return void
     */
    public function afterImportData(ImportDataEvent $event)
    {
        \Yii::info(
            sprintf(
                'Export data completed , token:%s, sheetName:%s, row:%s, result:%s',
                $event->importRowCallbackParam->importConfig->getToken(),
                $event->importRowCallbackParam->sheet->getName(),
                var_export($event->importRowCallbackParam->row, true),
                $event->isSuccess ? 'true' : 'false',
            ),
            $this->getCategory($event)
        );

    }

    /**
     * 异常信息处理
     *
     * @param ErrorEvent $event
     * @return void
     */
    public function error(ErrorEvent $event)
    {
        $token = $event->config->getToken();

        /**
         * @var  \Throwable $exception
         */
        $exception = $event->exception;

        \Yii::error(
            sprintf(
                'config:%s,token:%s, error:%s',
                get_class($event->config),
                $token,
                $exception->getMessage() . $exception->getTraceAsString(),
            ),
            $this->getCategory($event)
        );
    }


    /**
     * 获取日志分类
     *
     * @param Event $event
     * @return string
     */
    protected function getCategory(Event $event)
    {
        return sprintf('excel/%s', $event->name);
    }


}