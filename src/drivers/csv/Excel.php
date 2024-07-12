<?php

namespace vartruexuan\excel\drivers\csv;

use vartruexuan\excel\data\export\ExportConfig;
use vartruexuan\excel\data\import\ImportConfig;
use vartruexuan\excel\data\import\ImportData;
use vartruexuan\excel\ExcelAbstract;

class Excel extends ExcelAbstract
{

    public function exportExcel(ExportConfig $config): string
    {
        return '';
        // TODO: Implement export() method.
    }

    /**
     * 导入数据
     *
     * @param ImportConfig $config
     * @return ImportData
     */
    public function exportExcel(ImportConfig $config): ImportData
    {
        return new ImportData();
    }
}