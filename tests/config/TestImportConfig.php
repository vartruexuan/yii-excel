<?php


use vartruexuan\excel\data\import\ImportConfig;
use vartruexuan\excel\data\import\Sheet;
use vartruexuan\excel\ExcelAbstract;

class TestImportConfig extends ImportConfig
{
    public function getSheets(): array
    {
        $this->setSheets([
            new Sheet([
                'name' => 'sheet1',
                'isReturnSheetData' => true,
                'isSetHeader' => true,
                'headerMap' => [
                    '年龄' => 'age',
                    '姓名' => 'name',
                    '身高' => 'height',
                ],
                'callback' => function ($row, ExcelAbstract $excel, ImportConfig $config) {
                    // 执行行回调
                    var_dump($row);

                    sleep(10);

                    return true;
                }
            ])

        ]);
        return parent::getSheets();

    }


}