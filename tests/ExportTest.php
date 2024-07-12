<?php

use Carbon\Carbon;
use common\services\PlatformMenuService;
use vartruexuan\excel\data\export\Column;
use vartruexuan\excel\data\export\ExportConfig;
use vartruexuan\excel\drivers\xlswriter\Excel;
use vartruexuan\utils\helpers\Helper;

class ExportTest extends \PHPUnit\Framework\TestCase
{

    /**
     * 测试导出
     *
     * @return void
     */
    public function testExport()
    {
        Excel::instance()->export(new ExportConfig([
            'path' => '/www/export_'.Carbon::now()->format('Y_m_d_H_i_s_u').'.xlsx',
            'outPutType' => ExportConfig::OUT_PUT_TYPE_LOCAL,
            'sheets' => [
                new \vartruexuan\excel\data\export\Sheet([
                    'name' => 'sheet1',
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
                        $pageInfo = Helper::getPageInfo($total, $pageCount, $page);
                        return PlatformMenuService::instance()->getList(null,null,$pageInfo['limit'],$pageInfo['offset'],true);
                    },
                ]),
                new \vartruexuan\excel\data\export\Sheet([
                    'name' => 'sheet2',
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
                        $pageInfo = Helper::getPageInfo($total, $pageCount, $page);
                        return PlatformMenuService::instance()->getList(null,null,$pageInfo['limit'],$pageInfo['offset'],true);
                    },
                ])
            ]
        ]));

    }

}