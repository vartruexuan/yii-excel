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
        Excel::instance()->export(new TestExportConfig([
            'path' => '/www/export_' . Carbon::now()->format('Y_m_d_H_i_s_u') . '.xlsx',
            'outPutType' => ExportConfig::OUT_PUT_TYPE_LOCAL,
            // 额外参数
            'param' => [
                'age' => 1
            ]

        ]));
    }

}