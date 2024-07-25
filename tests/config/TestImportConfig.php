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
                'callback' => function (\vartruexuan\excel\data\import\ImportRowCallbackParam $importRowCallbackParam) {
                    // 执行行回调
                    var_dump($importRowCallbackParam->$row);
                    sleep(1);
                }
            ])

        ]);
        return $this->sheets;
    }


}