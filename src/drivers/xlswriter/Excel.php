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
        foreach ($config->getSheets() as $key => $sheet) {
            if ($key > 0) {
                $this->excel->addSheet($sheet->getName());
            }
            $this->excel->header($sheet->getHeaders());
            $count = $sheet->getCount();
            $pageCount = $sheet->getPageCount();
            $data = $sheet->getData();
            $this->initSheetProgress($token, $sheet->getName(), $sheet->getCount());
            if (is_callable($data)) {
                $pageNum = ceil($count / $pageCount);
                $page = 1;
                do {
                    $list = $this->exportDataFunc($data, $config, $page, $pageCount, $count, $sheet->getName());
                    $listCount = count($list ?? []);
                    if ($list) {
                        $this->excel->data($sheet->formatList($list));
                    }
                    $progressStatus = ($count <= 0 || ($listCount < $pageCount || $pageNum <= $page)) ? self::PROGRESS_STATUS_END : self::PROGRESS_STATUS_PROCESS;
                    $this->setSheetProgress($token, $sheet->getName(), $progressStatus, $listCount, $listCount);
                    $page++;
                } while ($count > 0 && $listCount >= $pageCount && ($pageNum >= $page));
            } else {
                $this->excel->data($sheet->formatList($data));
                $listCount = count($data ?? []);
                $this->setSheetProgress($token, $sheet->getName(), self::PROGRESS_STATUS_END, $listCount, $listCount);
            }
        }

        // 输出对应文件
        $this->excel->output();

        return $filePath;
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
        $this->checkFile($filePath);
        // 打开文件
        $this->excel->openFile($fileName);
        $sheetList = $this->excel->sheetList();

        // 初始化进度信息
        $this->setProgressInfo($token, array_map('strtolower', $sheetList), self::PROGRESS_STATUS_PROCESS);
        /**
         * 页配置
         *
         * @var Sheet $sheetConfig
         */
        foreach ($config->getSheets() as $sheetConfig) {
            $sheetName = $sheetConfig->name;
            if ($sheetConfig->readType == Sheet::SHEET_READ_TYPE_INDEX) {
                $sheetName = $sheetList[$sheetConfig->index];
            }

            $this->excel->openSheet($sheetName);

            $header = [];
            if ($sheetConfig->isSetHeader) {
                // 跳过指定行
                $header = $this->excel->nextRow();
                $header = $sheetConfig->getHeader($header);
            }
            // 返回全量数据
            if ($sheetConfig->isReturnSheetData) {
                $sheetData = $this->excel->getSheetData();
                $sheetDataCount = count($sheetData ?? []);
            }
            $this->initSheetProgress($token, $sheetName, $sheetDataCount ?? 0);
            if ($sheetConfig->callback || $header) {
                if ($sheetConfig->isReturnSheetData) {
                    foreach ($sheetData as $key => &$row) {
                        if ($header) {
                            $row = $sheetConfig->formatRowByHeader($row, $header);
                        }
                        // 执行回调
                        if ($sheetConfig->callback && is_callable($sheetConfig->callback)) {
                            $this->importRowCallback($sheetConfig->callback, $row, $this, $config, $sheetName);
                        }
                    }
                    $importData->addSheetData($sheetData, $sheetName);
                } else {
                    // 执行回调
                    if ($sheetConfig->callback) {
                        while (null !== $row = $this->excel->nextRow()) {
                            $this->importRowCallback($sheetConfig->callback, $sheetConfig->formatRowByHeader($row, $header), $this, $sheetConfig, $sheetName);
                        }
                    }
                }
            }
            $this->setSheetProgress($token, $sheetName, self::PROGRESS_STATUS_END);
        }

        // 删除临时文件
        @$this->deleteFile($filePath);

        $this->excel->close();
        return $importData;
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