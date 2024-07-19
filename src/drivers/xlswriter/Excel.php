<?php

namespace vartruexuan\excel\drivers\xlswriter;

use vartruexuan\excel\data\export\ExportConfig;
use vartruexuan\excel\data\import\ImportConfig;
use vartruexuan\excel\data\import\ImportData;
use vartruexuan\excel\data\import\Sheet;
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

        $filePath = $this->getTempFileName('ex_');
        $fileName = basename($filePath);
        $this->excel->fileName($fileName, ($config->sheets[0])->name ?? 'sheet1');

        $this->setProgressInfo($token, $config->getSheetNames(), self::PROGRESS_STATUS_PROCESS);
        /**
         * 写入页码数据
         *
         * @var \vartruexuan\excel\data\export\Sheet $sheet
         */
        foreach (array_values($config->getSheets()) as $index => $sheet) {
            $this->exportSheet($sheet, $config, $index);
        }

        $this->excel->output();

        return $filePath;
    }

    /**
     * 导出页码
     *
     * @param \vartruexuan\excel\data\export\Sheet $sheet
     * @param $index
     * @return void
     */
    protected function exportSheet(\vartruexuan\excel\data\export\Sheet $sheet, ExportConfig $config, $index = 0)
    {
        $token = $config->getToken();

        if ($index > 0) {
            $this->excel->addSheet($sheet->getName());
        }

        // header
        $this->excel->header($sheet->getHeaders());

        $totalCount = $sheet->getCount();
        $pageSize = $sheet->getPageSize();
        $dataCallback = $sheet->getData();
        $isCallback = is_callable($dataCallback);

        $this->initSheetProgress($token, $sheet->getName(), $totalCount);

        // 导出数据
        do {
            $page = 1;
            $pageNum = ceil($totalCount / $pageSize);

            $list = $dataCallback;
            if ($isCallback) {
                $list = $this->exportDataCallback($dataCallback, $config, $sheet, $page, $pageSize, $totalCount);
            }
            $listCount = count($list ?? []);
            if ($list) {
                $this->excel->data($sheet->formatList($list));
            }
            $isEnd = !$isCallback || $totalCount <= 0 || ($listCount < $pageSize || $pageNum <= $page);
            $progressStatus = $isEnd ? self::PROGRESS_STATUS_END : self::PROGRESS_STATUS_PROCESS;

            $this->setSheetProgress($token, $sheet->getName(), $progressStatus, $listCount, $listCount);

            $page++;

        } while ($totalCount > 0 && is_callable($dataCallback) && $listCount >= $pageSize && ($pageNum >= $page));

    }


    /**
     * 导入
     *
     * @param ImportConfig $config
     * @return ImportData
     */
    protected function importExcel(ImportConfig $config): ImportData
    {
        $token = $config->getToken();
        $importData = new ImportData([
            'token' => $token,
        ]);
        $filePath = $config->getTempPath();
        $fileName = basename($filePath);

        // 校验文件
        $this->checkFile($filePath);

        $this->excel->openFile($fileName);
        $sheetList = $this->excel->sheetList();


        $this->setProgressInfo($token, array_map('strtolower', $sheetList), self::PROGRESS_STATUS_PROCESS);
        /**
         * 页配置
         *
         * @var Sheet $sheet
         */
        foreach (array_values($config->getSheets()) as $sheet) {
            $this->importSheet($sheet, $config, $importData);
        }

        // 删除临时文件
        @$this->deleteFile($filePath);
        $this->excel->close();

        return $importData;
    }


    /**
     * 导出页码
     *
     * @param Sheet $sheet
     * @return void
     */
    protected function importSheet(Sheet $sheet, ImportConfig $config, ImportData &$importData)
    {
        $token = $config->getToken();

        $sheetName = $sheet->name;
        if ($sheet->readType == Sheet::SHEET_READ_TYPE_INDEX) {
            $sheetName = $sheetList[$sheet->index];
        }

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

        // 返回全量数据
        if ($sheet->isReturnSheetData) {
            $sheetData = $this->excel->getSheetData();
            $sheetDataCount = count($sheetData ?? []);
        }

        $this->initSheetProgress($token, $sheetName, $sheetDataCount ?? 0);

        if ($sheet->callback || $header) {
            if ($sheet->isReturnSheetData) {
                foreach ($sheetData as $key => &$row) {
                    $this->rowCallback($config,$sheet,$row, $header);
                }
                $importData->addSheetData($sheetData, $sheetName);
            } else {
                // 执行回调
                while (null !== $row = $this->excel->nextRow()) {
                    $this->rowCallback($config,$sheet,$row, $header);
                }
            }
        }
        $this->setSheetProgress($token, $sheetName, self::PROGRESS_STATUS_END);
    }

    /**
     * 执行行回调
     *
     * @param $row
     * @param $header
     * @return void
     */
    protected function rowCallback(ImportConfig $config,Sheet $sheet,$row, $header = null)
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