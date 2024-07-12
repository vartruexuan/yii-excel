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
        $importData = Excel::instance()->import(new \vartruexuan\excel\data\import\ImportConfig([
             'path' => '../files/ceshi.xlsx',
           // 'path' => 'https://pubbucket-1259747003.cos.ap-guangzhou.myqcloud.com/ceshi.xlsx',

            'sheets' => [
                new Sheet([
                    'name' => 'sheet1',
                    'isReturnSheetData' => false,
                    'isSetHeader' => true,
                    'headerMap' => [
                        '年龄' => 'age',
                        '姓名' => 'name',
                        '身高' => 'height',
                    ],
                    'callback' => function ($row) {
                        // 执行行回调
                        var_dump($row);
                    }
                ]),
            ],
        ]));

    }

}