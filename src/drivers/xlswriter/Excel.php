<?php

namespace vartruexuan\excel\drivers\xlswriter;

use vartruexuan\excel\data\export\ExportConfig;
use vartruexuan\excel\data\import\ImportConfig;
use vartruexuan\excel\data\import\ImportData;
use vartruexuan\excel\data\import\Sheet;
use vartruexuan\excel\events\ExportEvent;
use vartruexuan\excel\events\ExportSheetEvent;
use vartruexuan\excel\events\ImportDataEvent;
use vartruexuan\excel\events\ImportEvent;
use vartruexuan\excel\events\ImportSheetEvent;
use vartruexuan\excel\ExcelAbstract;
use vartruexuan\excel\exceptions\ExcelException;
use yii\helpers\FileHelper;

class Excel extends ExcelAbstract
{

    /**
     * 操作对象
     *
     * @var
     */
    public \Vtiful\Kernel\Excel $excel;


    public function init()
    {
        $this->excel = new \Vtiful\Kernel\Excel([
            'path' => $this->getTempDir(),
        ]);
        parent::init();
    }


    /**
     * 导出
     *
     * @param ExportConfig $config
     * @return void
     */
    protected function exportExcel(ExportConfig $config): string
    {
        $token = $config->getToken();

        $event = new ExportEvent([
            'exportConfig' => $config,
        ]);

        $filePath = $this->getTempFileName('ex_');
        $fileName = basename($filePath);
        $this->excel->fileName($fileName, ($config->sheets[0])->name ?? 'sheet1');

        $this->trigger(static::EVENT_BEFORE_EXPORT_EXCEL, $event);

        /**
         * 写入页码数据
         *
         * @var \vartruexuan\excel\data\export\Sheet $sheet
         */
        foreach (array_values($config->getSheets()) as $index => $sheet) {
            $this->exportSheet($sheet, $config, $index);
        }

        $this->excel->output();

        $this->trigger(static::EVENT_AFTER_EXPORT_EXCEL, $event);

        return $filePath;
    }

    /**
     * 导出页码
     *
     * @param \vartruexuan\excel\data\export\Sheet $sheet
     * @param ExportConfig $config
     * @param int $index
     * @return void
     */
    protected function exportSheet(\vartruexuan\excel\data\export\Sheet $sheet, ExportConfig $config, $index = 0)
    {
        $token = $config->getToken();

        if ($index > 0) {
            $this->excel->addSheet($sheet->getName());
        }

        $event = new ExportSheetEvent([
            'exportConfig' => $config,
            'sheet' => $sheet,
        ]);

        $this->trigger(static::EVENT_BEFORE_EXPORT_SHEET, $event);

        // header
        $this->excel->header($sheet->getHeaders());

        $totalCount = $sheet->getCount();
        $pageSize = $sheet->getPageSize();
        $data = $sheet->getData();
        $isCallback = is_callable($data);

        $page = 1;
        $pageNum = ceil($totalCount / $pageSize);

        // 导出数据
        do {
            $list = $dataCallback = $data;

            if (!$isCallback) {
                $totalCount = 0;
                $dataCallback = function () use (&$totalCount, $list) {
                    return $list;
                };
            }

            $list = $this->exportDataCallback($dataCallback, $config, $sheet, $page, $pageSize, $totalCount);

            $listCount = count($list ?? []);

            if ($list) {
                $this->excel->data($sheet->formatList($list));
            }

            $isEnd = !$isCallback || $totalCount <= 0 || ($listCount < $pageSize || $pageNum <= $page);

            $page++;
        } while (!$isEnd);

        $this->trigger(static::EVENT_AFTER_EXPORT_SHEET, $event);

    }


    /**
     * 导入
     *
     * @param ImportConfig $config
     * @return ImportData
     * @throws ExcelException
     */
    protected function importExcel(ImportConfig $config): ImportData
    {
        $token = $config->getToken();

        $event = new ImportEvent([
            'importConfig' => $config,
        ]);

        $importData = new ImportData([
            'token' => $token,
        ]);
        $filePath = $config->getTempPath();
        $fileName = basename($filePath);

        // 校验文件
        $this->checkFile($filePath);

        $this->excel->openFile($fileName);

        $sheetList = $this->excel->sheetList();
        $sheetNames = [];

        $sheets = array_map(function ($sheet) use (&$sheetNames, $sheetList) {
            $sheetName = $sheet->name;
            if ($sheet->readType == Sheet::SHEET_READ_TYPE_INDEX) {
                $sheetName = $sheetList[$sheet->index];
                $sheet->name = $sheetName;
            }
            $sheetNames[] = $sheetName;
            return $sheet;
        }, array_values($config->getSheets()));

        $event->sheetNames = $sheetNames;

        $this->trigger(static::EVENT_BEFORE_IMPORT_EXCEL, $event);

        /**
         * 页配置
         *
         * @var Sheet $sheet
         */
        foreach ($sheets as $sheet) {
            $this->importSheet($sheet, $config, $importData);
        }

        // 删除临时文件
        @$this->deleteFile($filePath);
        $this->excel->close();

        $this->trigger(static::EVENT_AFTER_IMPORT_EXCEL, $event);
        return $importData;
    }


    /**
     * 导出页码
     *
     * @param Sheet $sheet
     * @param ImportConfig $importConfig
     * @param ImportData $importData
     * @return void
     */
    protected function importSheet(Sheet $sheet, ImportConfig $importConfig, ImportData &$importData)
    {
        $token = $importConfig->getToken();

        $event = new ImportSheetEvent([
            'importConfig' => $importConfig,
            'sheet' => $sheet,
        ]);

        $sheetName = $sheet->name;

        $this->trigger(static::EVENT_BEFORE_IMPORT_SHEET, $event);

        $this->excel->openSheet($sheetName);

        $header = [];

        if ($sheet->isSetHeader) {
            if ($sheet->headerIndex > 1) {
                // 跳过指定行
                $this->excel->setSkipRows($sheet->headerIndex - 1);
            }
            $header = $this->excel->nextRow();
            $header = $sheet->getHeader($header);
        }

        if ($sheet->callback || $header) {
            if ($sheet->isReturnSheetData) {
                // 返回全量数据
                $sheetData = $this->excel->getSheetData();
                foreach ($sheetData as $key => &$row) {
                    $this->rowCallback($importConfig, $sheet, $row, $header);
                }
                $importData->addSheetData($sheetData, $sheetName);
            } else {
                // 执行回调
                while (null !== $row = $this->excel->nextRow()) {
                    $this->rowCallback($importConfig, $sheet, $row, $header);
                }
            }
        }

        $this->trigger(static::EVENT_AFTER_IMPORT_SHEET, $event);
    }

    /**
     * 执行行回调
     *
     * @param ImportConfig $config
     * @param Sheet $sheet
     * @param $row
     * @param null $header
     * @return void
     */
    protected function rowCallback(ImportConfig $config, Sheet $sheet, $row, $header = null)
    {
        if ($header) {
            $row = $sheet->formatRowByHeader($row, $header);
        }
        // 执行回调
        if (is_callable($sheet->callback)) {
            $this->importRowCallback($sheet->callback, $config, $sheet, $row);
        }
    }

    /**
     * 校验文件mimeType类型
     *
     * @param $filePath
     * @return void
     * @throws ExcelException
     */
    protected function checkFile($filePath)
    {
        // 本地地址
        if (!file_exists($filePath)) {
            throw new ExcelException('File does not exist');
        }
        // 校验mime type
        $mimeType = FileHelper::getMimeType($filePath);
        if (!in_array($mimeType, [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'application/octet-stream',
        ])) {
            throw new ExcelException('File mime type error');
        }
    }

}