<?php


use common\services\PlatformMenuService;
use vartruexuan\excel\data\export\Column;
use vartruexuan\excel\data\export\ExportConfig;
use vartruexuan\excel\data\export\Sheet;
use vartruexuan\excel\ExcelAbstract;
use vartruexuan\utils\helpers\Helper;

class TestExportConfig extends ExportConfig
{

    /**
     * 获取地址
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * 获取页码配置
     *
     * @return Sheet[]
     */
    public function getSheets()
    {
        return [
            new \vartruexuan\excel\data\export\Sheet([
                'name' => 'sheet1',
                'pageCount' => 5000,
                'columns' => [
                    new Column([
                        'title' => 'uniqId',
                        'key' => 'uniqId',
                        'field' => 'uniqId',
                    ]),
                    new Column([
                        'title' => '标题',
                        'key' => 'title',
                        'field' => 'title',
                    ]),
                    new Column([
                        'title' => 'component',
                        'key' => 'component',
                        'field' => 'component',
                    ]),
                ],
                'count' => PlatformMenuService::instance()->getCount(),
                'data' => function (ExportConfig $config, $page, $pageSize, $total, $sheetName, ExcelAbstract $excel) {
                    $pageInfo = Helper::getPageInfo($total, $pageCount, $page, null, [1, $pageCount]);
                    return PlatformMenuService::instance()->getList(null, null, $pageInfo['limit'], $pageInfo['offset'], true);
                },
            ]),
            new \vartruexuan\excel\data\export\Sheet([
                'name' => 'sheet2',
                'pageCount' => 5000,
                'columns' => [
                    new Column([
                        'title' => 'uniqId',
                        'key' => 'uniqId',
                        'field' => 'uniqId',
                    ]),
                    new Column([
                        'title' => '标题',
                        'key' => 'title',
                        'field' => 'title',
                    ]),
                    new Column([
                        'title' => 'component',
                        'key' => 'component',
                        'field' => 'component',
                    ]),
                ],
                'count' => PlatformMenuService::instance()->getCount(),
                'data' => function ($page, $pageCount, $total) {
                    var_dump($page, $pageCount, $total);
                    $pageInfo = Helper::getPageInfo($total, $pageCount, $page, null, [1, $pageCount]);
                    return PlatformMenuService::instance()->getList(null, null, $pageInfo['limit'], $pageInfo['offset'], true);
                },
            ])
        ];
    }

    /**
     * 获取输出类型
     *
     * @return string
     */
    public function getOutPutType()
    {
        return $this->outPutType;
    }


}