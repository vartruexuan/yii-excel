<?php

namespace vartruexuan\excel;

use Overtrue\Http\Client;
use creocoder\flysystem\Filesystem;
use vartruexuan\excel\data\export\ExportCallbackParam;
use vartruexuan\excel\data\export\ExportConfig;
use vartruexuan\excel\data\export\ExportData;
use vartruexuan\excel\data\import\ImportConfig;
use vartruexuan\excel\data\import\ImportData;
use vartruexuan\excel\data\import\ImportRowCallbackParam;
use vartruexuan\excel\data\import\Sheet;
use vartruexuan\excel\events\ErrorEvent;
use vartruexuan\excel\events\ExportEvent;
use vartruexuan\excel\events\ImportEvent;
use vartruexuan\excel\exceptions\ExcelException;
use vartruexuan\excel\jobs\ExportJob;
use vartruexuan\excel\jobs\ImportJob;
use vartruexuan\excel\utils\Helper;
use Ramsey\Uuid\Uuid;
use yii\base\Component;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;
use yii\base\BaseObject;
use yii\base\StaticInstanceTrait;
use yii\di\Instance;
use yii\helpers\Inflector;
use yii\helpers\Json;
use yii\queue\Queue;
use yii\redis\Connection;

abstract class ExcelAbstract extends Component
{
    use StaticInstanceTrait;
    use ProgressTrait;

    /**
     * 导出之前
     */
    const ENVENT_BEFORE_EXPORT = 'beforeExport';
    /**
     * 导出之后
     */
    const ENVENT_AFTER_EXPORT = 'afterExport';

    /**
     * 导入之前
     */
    const ENVENT_BEFORE_IMPORT = 'beforeImport';

    /**
     * 导入之后
     */
    const ENVENT_AFTER_IMPORT = 'afterImport';

    /**
     * 设置进度之后
     */
    const EVENT_AFTER_PROGRESS = 'afterProgress';

    /**
     * 发送错误
     */
    const EVENT_ERROR = 'error';


    /**
     * redis实例
     *
     * @var Connection
     */
    public $redis = 'redis';

    /**
     * 队列实例
     *
     * @var \yii\queue\Queue
     */
    public $queue = 'queue';


    /**
     * 文件操作对象
     *
     * @var \creocoder\flysystem\Filesystem
     */
    public $fileSystem = 'filesystem';


    /**
     * 初始化
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        if (!$this->redis instanceof Connection) {
            $this->redis = Instance::ensure($this->redis, '\yii\redis\Connection');
        }

        if (!$this->queue instanceof Queue) {
            $this->queue = Instance::ensure($this->queue, '\yii\queue\Queue');
        }

        if (!$this->fileSystem instanceof Filesystem) {
            $this->fileSystem = Instance::ensure($this->fileSystem, '\creocoder\flysystem\Filesystem');
        }
    }

    /**
     * 导出
     *
     * @param ExportConfig $config 导出配置
     *
     * @return string 返回文件地址
     */
    abstract protected function exportExcel(ExportConfig $config): string;

    /**
     * 导入操作
     *
     * @param ImportConfig $config 导入配置
     * @return ImportData 导入数据
     */
    abstract protected function importExcel(ImportConfig $config): ImportData;


    /**
     * 导出
     *
     * @param ExportConfig $config
     * @return void
     */
    public function export(ExportConfig $config)
    {
        $config = $this->formatExportConfig($config);
        try {
            $token = $config->getToken();
            $exportData = new ExportData([
                'token' => $token,
            ]);

            $event = new ExportEvent([
                'exportConfig' => $config,
            ]);
            $this->trigger(self::ENVENT_BEFORE_EXPORT, $event);

            $this->initProgressInfo($token);
            // 异步
            if ($config->getIsAsync()) {
                // 异步不支持直接输出
                if ($config->getOutPutType() == ExportConfig::OUT_PUT_TYPE_OUT) {
                    throw new ExcelException('Async does not support output type ExportConfig::OUT_PUT_TYPE_OUT');
                }
                $exportData->queueId = $this->pushExportQueue($config->setToken($token));
                return $exportData;
            }
            $filePath = $this->exportExcel($config);

            // 文件输出
            if ($config->outPutType == ExportConfig::OUT_PUT_TYPE_UPLOAD) {
                // 上传文件
                if (!$this->fileSystem->writeStream($config->path, fopen($filePath, 'r+'))) {
                    throw new ExcelException('upload file fail');
                }
                if (method_exists($config, 'getUrl')) {
                    $url = $config->getUrl($config->path);
                } else {
                    $url = $this->getFileSystemUrl($config->path);
                }
                $exportData->setPath($url);

                $this->setProgressInfo($token, null, self::PROGRESS_STATUS_END, [
                    'url' => $url,
                ]);
            } else if ($config->outPutType == ExportConfig::OUT_PUT_TYPE_LOCAL) {
                if (copy($filePath, $config->getPath()) === false) {
                    throw new ExcelException('copy file fail');
                }
                $exportData->setPath($config->getPath());
                $this->setProgressInfo($token, null, self::PROGRESS_STATUS_END);
            } else {
                // Set Header
                header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
                header('Content-Disposition: attachment;filename="' . urlencode($config->getFileName()) . '"');
                header('Content-Length: ' . filesize($filePath));
                header('Content-Transfer-Encoding: binary');
                header('Cache-Control: must-revalidate');
                header('Cache-Control: max-age=0');
                header('Pragma: public');

                ob_clean();
                flush();
                // 直接输出
                if (copy($filePath, 'php://output') === false) {
                    throw new ExcelException('copy file fail');
                }
            }
            $this->trigger(self::ENVENT_AFTER_EXPORT, $event);
            // 删除临时文件
            @$this->deleteFile($filePath);
            $this->setProgressInfo($token, null, self::PROGRESS_STATUS_END);
        } catch (ExcelException $excelException) {
            // 失败
            $this->setProgressInfo($token, null, self::PROGRESS_STATUS_FAIL);
            $this->setProgressMessage($token, $excelException->getMessage());
            $this->trigger(self::EVENT_ERROR, new ErrorEvent([
                'config' => $config,
                'exception' => $excelException,
            ]));
            throw $excelException;
        } catch (\Throwable $exception) {
            $this->setProgressInfo($token, null, self::PROGRESS_STATUS_FAIL);
            $this->setProgressMessage($token, '导出异常');
            $this->trigger(self::EVENT_ERROR, new ErrorEvent([
                'config' => $config,
                'exception' => $exception,
            ]));
            \Yii::error($exception->getMessage(), 'vartruexuan/excel/export');
            throw $exception;
        }

        return $exportData;
    }

    /**
     * 导入
     *
     * @param ImportConfig $config
     * @return void
     */
    public function import(ImportConfig $config)
    {
        $config = $this->formatImportConfig($config);
        try {
            $token = $config->getToken();
            $importData = new ImportData([
                'token' => $token,
            ]);

            $event = new ImportEvent([
                'importConfig' => $config,
            ]);
            $this->trigger(self::ENVENT_BEFORE_IMPORT, $event);

            $this->initProgressInfo($token);
            // 异步
            if ($config->getIsAsync()) {
                $importData->queueId = $this->pushImportQueue($config->setToken($token));
                return $importData;
            }

            // 构建临时文件
            $filePath = $this->getTempFileName();
            $config->setTempPath($filePath);
            if (!$this->isRemoteUrl($config->getPath())) {
                // 本地文件
                if (!is_file($config->getPath())) {
                    throw new ExcelException('path not exists');
                }
                if (!FileHelper::copy($config->getPath(), $filePath)) {
                    throw new ExcelException('file copy error');
                }
            } else {
                // 远程文件
                if (!$this->downloadFile($config->getPath(), $filePath)) {
                    throw new ExcelException('file download error');
                }
            }

            // 执行导入
            $importData = $this->importExcel($config);

            $this->trigger(self::ENVENT_AFTER_IMPORT, $event);

            $this->setProgressInfo($token, null, self::PROGRESS_STATUS_END);

        } catch (ExcelException $excelException) {
            // 失败
            $this->setProgressInfo($token, null, self::PROGRESS_STATUS_FAIL);
            $this->setProgressMessage($token, $excelException->getMessage());
            $this->trigger(self::EVENT_ERROR, new ErrorEvent([
                'config' => $config,
                'exception' => $excelException,
            ]));
            throw $excelException;
        } catch (\Throwable $exception) {
            $this->setProgressInfo($token, null, self::PROGRESS_STATUS_FAIL);
            $this->setProgressMessage($token, '导入异常');
            $this->trigger(self::EVENT_ERROR, new ErrorEvent([
                'config' => $config,
                'exception' => $exception,
            ]));
            \Yii::error($exception->getMessage(), 'vartruexuan/excel/import');
            throw $exception;
        }

        return $importData;
    }


    /**
     * 导入异步队列
     *
     * @param ImportConfig $config
     * @return string|null
     */
    protected function pushImportQueue(ImportConfig $config)
    {
        return $this->queue->push(new ImportJob([
            'importConfig' => $config,
            'componentId' => $this->getCommandId(),
        ]));
    }

    /**
     * 导出异步队列
     *
     * @param ExportConfig $config
     * @return string|null
     */
    protected function pushExportQueue(ExportConfig $config)
    {
        return $this->queue->push(new ExportJob([
            'exportConfig' => $config,
            'componentId' => $this->getCommandId(),
        ]));
    }


    /**
     * 格式化导入配置数据
     *
     * @param ImportConfig $config
     * @return ImportConfig
     */
    protected function formatImportConfig(ImportConfig $config)
    {
        if (!$config->getToken()) {
            $config->setToken($this->buildToken());
        }
        // 格式化页码
        $sheets = $config->getSheets();
        foreach ($sheets as &$sheet) {
            if (!$sheet instanceof Sheet) {
                $sheet = new Sheet($sheet);
            }
        }
        $config->setSheets($sheets);
        return $config;
    }

    /**
     * 格式化导出配置
     *
     * @param ExportConfig $config
     * @return ExportConfig
     */
    protected function formatExportConfig(ExportConfig $config)
    {

        if (!$config->getToken()) {
            $config->setToken($this->buildToken());
        }
        return $config;
    }


    /**
     * 获取临时目录
     *
     * @return string
     */
    protected function getTempDir()
    {
        return sys_get_temp_dir();
    }

    /**
     * 构建一个临时文件
     *
     * @return false|string
     */
    protected function getTempFileName($prefix = 'ex_')
    {
        return tempnam($this->getTempDir(), $prefix);
    }


    /**
     * 删除文件
     *
     * @param $filepath
     * @return bool
     */
    protected function deleteFile($filepath)
    {
        return Helper::instance()->deleteFile($filepath);
    }

    /**
     * 远程文件地址
     *
     * @param $remotePath
     * @param $filepath
     * @return void
     */
    protected function downloadFile($remotePath, $filePath)
    {
        return Helper::instance()->downloadFile($remotePath, $filePath);
    }

    /**
     * 是否是url地址
     *
     * @param $url
     * @return void
     */
    protected function isRemoteUrl($url)
    {
        return Helper::instance()->isUrl($url);
    }

    /**
     * 构建token
     *
     * @return string
     * @throws \yii\base\Exception
     */
    protected function buildToken()
    {
        // return \Yii::$app->security->generateRandomString();
        return Helper::instance()->uuid4();
    }

    /**
     * 导入行回调
     *
     * @param callable $callback
     * @param ImportConfig $config
     * @param Sheet $sheet
     * @param array $row
     *
     * @return mixed|null
     */
    protected function importRowCallback(callable $callback, ImportConfig $config, Sheet $sheet, array $row)
    {
        return call_user_func($callback, new ImportRowCallbackParam([
            'excel' => $this,
            'importConfig' => $config,
            'row' => $row,
            'sheet' => $sheet,
        ]));
    }


    /**
     * 导出数据回调
     *
     * @param callable $callback 回调
     * @param int $page 页码
     * @param int $pageCount 限制每页数量
     * @param ?int $count 总数
     * @param $param 额外参数
     * @param string $token
     * @param string $sheetName
     * @return mixed
     */
    protected function exportDataCallback(callable $callback, ExportConfig $config, \vartruexuan\excel\data\export\Sheet $sheet, int $page, int $pageSize, ?int $totalCount)
    {
        return call_user_func($callback, new ExportCallbackParam([
            'exportConfig' => $config,
            'page' => $page,
            'pageSize' => $pageSize,
            'sheet' => $sheet,
            'totalCount' => $totalCount,
            'excel' => $this,
        ]));
    }


    /**
     * 获取命令ID(组件ID)
     *
     * @return string command id
     * @throws
     */
    protected function getCommandId()
    {
        foreach (\Yii::$app->getComponents(false) as $id => $component) {
            if ($component === $this) {
                return Inflector::camel2id($id);
            }
        }
        throw new InvalidConfigException('excel must be an application component.');
    }

    /**
     * 获取文件地址
     *
     * @param $url
     * @return void
     */
    protected function getFileSystemUrl(string $path, bool $isSign = false)
    {
        // cdn
        if ($this->fileSystem->cdn) {
            return $this->fileSystem->getAdapter()->applyPathPrefix($path);
        }

        // 签名地址
        if ($isSign) {
            return $this->fileSystem->getAdapter()->getUrl($path);
        }
        // 原始地址
        $scheme = $this->fileSystem->scheme ? $this->fileSystem->scheme : 'http';
        $sourcePath = $this->fileSystem->getAdapter()->getSourcePath($path);
        return "{$scheme}://{$sourcePath}";
    }

}