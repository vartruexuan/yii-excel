<?php

namespace vartruexuan\excel\behaviors;

use vartruexuan\excel\ExcelAbstract;

/**
 * excel日志
 */
class ExcelLogBehavior extends LogBehavior
{
    public function events()
    {
        return [
            ExcelAbstract::ENVENT_AFTER_EXPORT => 'afterExport',
            ExcelAbstract::ENVENT_BEFORE_EXPORT => 'beforeExport',
            ExcelAbstract::ENVENT_AFTER_IMPORT => 'afterImport',
            ExcelAbstract::ENVENT_BEFORE_IMPORT => 'beforeImport',
            ExcelAbstract::EVENT_AFTER_PROGRESS => 'afterProgress',
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

    }

    /**
     * 导出之后
     *
     * @param ExportEvent $event
     * @return void
     * @throws ServiceException
     */
    public function afterExport(ExportEvent $event)
    {

    }

    /**
     * 导入之前
     *
     * @param ImportEvent $event
     * @return void
     */
    public function beforeImport(ImportEvent $event)
    {
    }

    /**
     * 导入之后
     *
     * @param ImportEvent $event
     * @return void
     */
    public function afterImport(ImportEvent $event)
    {
    }

    /**
     * 设置进度
     *
     * @param ProgressEvent $event
     * @return void
     */
    public function afterProgress(ProgressEvent $event)
    {

    }

    /**
     * 异常
     *
     * @param ErrorEvent $event
     * @return void
     */
    public function error(ErrorEvent $event)
    {
    }


}