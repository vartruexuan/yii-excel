<?php


use common\services\PlatformMenuService;
use Random\RandomException;
use vartruexuan\excel\data\export\Column;
use vartruexuan\excel\data\export\ExportCallbackParam;
use vartruexuan\excel\data\export\ExportConfig;
use vartruexuan\excel\data\export\Sheet;
use vartruexuan\excel\ExcelAbstract;
use vartruexuan\utils\helpers\Helper;

class TestExportConfig extends ExportConfig
{

    public bool $isAsync = true;

    /**
     * 页配置
     *
     * @return array|Sheet[]
     * @throws \Exception
     */
    public function getSheets()
    {
        $this->setSheets([
            new Sheet([
                'name' => 'sheet1',
                'columns' => [
                    new Column([
                        'title' => '姓名',
                        'field' => 'name',
                    ]),
                    new Column([
                        'title' => '年龄',
                        'field' => 'age',
                    ]),
                    new Column([
                        'title' => '身高',
                        'field' => 'height',
                    ]),
                    new Column([
                        'title' => '职业',
                        'field' => 'post',
                    ]),
                    new Column([
                        'title'=>'数据页',
                        'field'=>'page',
                    ])
                ],
                'count' => $this->dataCount(),
                'data' => [$this, 'dataCallback'],
                'pageSize' => 500,
            ])
        ]);
        return $this->sheets;
    }

    /**
     * 数据回调
     *
     * @param ExportCallbackParam $exportCallbackParam
     * @return array|array[]
     * @throws RandomException
     */
    public function dataCallback(ExportCallbackParam $exportCallbackParam)
    {
        // 参数条件
        // $this->getParam();

        $list = [];
        for ($i = 0; $i < $exportCallbackParam->pageSize; $i++) {
            $list[] = [
                'name' => '郭先生' . random_int(1, 100000),
                'age' => random_int(10, 100),
                'height' => random_int(100, 180),
                'post' => '无业',
                'page' => $exportCallbackParam->page,
            ];
        }
        usleep(500);
        return $list;
    }

    /**
     * 数据数量
     *
     * @return array|array[]|int
     * @throws \Exception
     */
    public function dataCount()
    {
        return 1000;
    }


    /**
     * 获取地址
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

}