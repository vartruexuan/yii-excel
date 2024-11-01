<?php

namespace vartruexuan\excel\behaviors;

use vartruexuan\excel\data\progress\ProgressData;
use vartruexuan\excel\events\ErrorEvent;
use vartruexuan\excel\events\ExportDataEvent;
use vartruexuan\excel\events\ExportEvent;
use vartruexuan\excel\events\ExportOutputEvent;
use vartruexuan\excel\events\ExportSheetEvent;
use vartruexuan\excel\events\ImportDataEvent;
use vartruexuan\excel\events\ImportEvent;
use vartruexuan\excel\events\ImportSheetEvent;
use vartruexuan\excel\ExcelAbstract;
use vartruexuan\excel\ExcelProgress;
use yii\base\Behavior;
use yii\base\Event;
/**
 * 进度行为
 */
class ExcelProgressBehavior extends Behavior
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
            ExcelAbstract::EVENT_BEFORE_EXPORT_OUTPUT => 'beforeExportOutput',

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
        $token = $event->exportConfig->getToken();
        $this->getProgressInstance($event)->initProgressRecord($token);

    }

    /**
     * 导出之后
     *
     * @param ExportEvent $event
     * @return void
     */
    public function afterExport(ExportEvent $event)
    {

        $token = $event->exportConfig->getToken();
        $this->getProgressInstance($event)->setProgressRecord($token, null, ProgressData::PROGRESS_STATUS_END, [
            'url' => $event->exportData->path,
        ]);
    }

    /**
     * 执行导出之前
     *
     * @param ExportEvent $event
     * @return void
     */
    public function beforeExportExcel(ExportEvent $event)
    {
        $token = $event->exportConfig->getToken();
        $this->getProgressInstance($event)->setProgressRecord($token, $event->exportConfig->getSheetNames(), ProgressData::PROGRESS_STATUS_PROCESS);
    }

    
  
    

    /**
     * 执行导出之后
     *
     * @param ExportEvent $event
     * @return void
     */
    public function afterExportExcel(ExportEvent $event)
    {

    }

    /**
     * 导出sheet之前
     *
     * @param ExportSheetEvent $event
     * @return void
     */
    public function beforeExportSheet(ExportSheetEvent $event)
    {
        $this->getProgressInstance($event)->initSheetProgress($event->exportConfig->getToken(), $event->sheet->getName(), $event->sheet->getCount());
    }


    /**
     * 导出sheet之后
     *
     * @param ExportSheetEvent $event
     * @return void
     */
    public function afterExportSheet(ExportSheetEvent $event)
    {
        $token = $event->exportConfig->getToken();
        $sheetName = $event->sheet->name;
        $this->getProgressInstance($event)->setSheetProgress($token, $sheetName, ProgressData::PROGRESS_STATUS_END);
    }


    public function beforeExportData(ExportDataEvent $event)
    {

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
        $sheet = $event->exportCallbackParam->sheet;
        $listCount = count($event->list);
        $this->getProgressInstance($event)->setSheetProgress(
            $token,
            $sheet->getName(),
            ProgressData::PROGRESS_STATUS_PROCESS,
            0,
            $listCount,
            $listCount
        );
    }

    /**
     * 
     * 导出输出之前
     * 
     * @param ExportOutputEvent $event
     * @return void
     */
    public function beforeExportOutput(ExportOutputEvent $event)
    {
        $token = $event->exportConfig->getToken();
        $this->getProgressInstance($event)->setProgressRecord($token, null, ProgressData::PROGRESS_STATUS_OUTPUT);
    }
    

    /**
     * 导入之前
     *
     * @param ImportEvent $event
     * @return void
     */
    public function beforeImport(ImportEvent $event)
    {
        $this->getProgressInstance($event)->initProgressRecord($event->importConfig->getToken());
    }

    /**
     * 导入之后
     *
     * @param ImportEvent $event
     * @return void
     */
    public function afterImport(ImportEvent $event)
    {
        $this->getProgressInstance($event)->setProgressRecord(
            $event->importConfig->getToken(),
            null,
            ProgressData::PROGRESS_STATUS_END
        );
    }

    /**
     * 执行导出之前
     *
     * @param ImportEvent $event
     * @return void
     */
    public function beforeImportExcel(ImportEvent $event)
    {
        $this->getProgressInstance($event)->setProgressRecord(
            $event->importConfig->getToken(),
            array_map('strtolower', $event->sheetNames),
            ProgressData::PROGRESS_STATUS_PROCESS
        );
    }

    /**
     * 执行导出之后
     *
     * @param ImportEvent $event
     * @return void
     */
    public function afterImportExcel(ImportEvent $event)
    {
        $this->getProgressInstance($event)->setProgressRecord($event->importConfig->getToken(), null, ProgressData::PROGRESS_STATUS_END);
    }


    /**
     * 导入sheet之前
     *
     * @param ImportSheetEvent $event
     * @return void
     */
    public function beforeImportSheet(ImportSheetEvent $event)
    {
        $token = $event->importConfig->getToken();
        $sheetName = $event->sheet->name;
        $this->getProgressInstance($event)->initSheetProgress($token, $sheetName, 0);
    }

    /**
     * 导入sheet之后
     *
     * @param ImportSheetEvent $event
     * @return void
     */
    public function afterImportSheet(ImportSheetEvent $event)
    {
        $token = $event->importConfig->getToken();
        $sheetName = $event->sheet->name;
        $this->getProgressInstance($event)->setSheetProgress($token, $sheetName, ProgressData::PROGRESS_STATUS_END);
    }


    /**
     * 导入数据之前
     *
     * @param ImportDataEvent $event
     * @return void
     */
    public function beforeImportData(ImportDataEvent $event)
    {

    }

    /**
     * 导入数据之后
     *
     * @param ImportDataEvent $event
     * @return void
     */
    public function afterImportData(ImportDataEvent $event)
    {
        $token = $event->importRowCallbackParam->importConfig->getToken();
        $sheetName = $event->importRowCallbackParam->sheet->name;
        $this->getProgressInstance($event)->setSheetProgress($token, $sheetName,
            ProgressData::PROGRESS_STATUS_PROCESS,
            1,
            1,
            $event->isSuccess ? 1 : 0,
            $event->isSuccess ? 0 : 1,
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

        // 设置进度信息
        $this->getProgressInstance($event)->setProgressRecord($token, null, ProgressData::PROGRESS_STATUS_FAIL);
        $this->getProgressInstance($event)->pushProgressMessage($token, $exception?->getMessage());
    }


    /**
     * 获取进度操作对象
     *
     * @return ExcelProgress
     */
    protected function getProgressInstance(Event $event)
    {
        if ($event->sender instanceof ExcelAbstract) {
            $progressInstance = $event->sender->progress;
        } else {
            $progressInstance = ExcelProgress::instance();
        }
        return $progressInstance;
    }

}