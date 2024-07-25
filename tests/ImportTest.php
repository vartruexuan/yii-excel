<?php

use vartruexuan\excel\data\import\Sheet;
use vartruexuan\excel\drivers\xlswriter\Excel;

class ImportTest extends \PHPUnit\Framework\TestCase
{


    /**
     * 测试导入
     *
     * @return void
     */
    public function testImport()
    {
        $importData = Excel::instance()->import(new TestImportConfig([
            'path' => '../files/ceshi.xlsx'
        ]));

    }

}