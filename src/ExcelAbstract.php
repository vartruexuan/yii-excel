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
use vartruexuan\excel\data\import\Sheet as ImportSheet;
use vartruexuan\excel\data\export\Sheet as ExportSheet;
use vartruexuan\excel\events\ErrorEvent;
use vartruexuan\excel\events\ExportDataEvent;
use vartruexuan\excel\events\ExportEvent;
use vartruexuan\excel\events\ExportOutputEvent;
use vartruexuan\excel\events\ImportDataEvent;
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
use yii\queue\Queue;
use yii\redis\Connection;

abstract class ExcelAbstract extends Component
{
    use StaticInstanceTrait;

    /**
     * 导出之前
     */
    const ENVENT_BEFORE_EXPORT = 'beforeExport';

    /**
     * 导出之后
     */
    const ENVENT_AFTER_EXPORT = 'afterExport';

    /**
     * 执行导入之前
     */

    const EVENT_BEFORE_EXPORT_EXCEL = 'beforeExportExcel';

    /**
     * 执行导入之后
     */
    const EVENT_AFTER_EXPORT_EXCEL = 'afterExportExcel';


    /**
     * 导出sheet之前
     */
    const EVENT_BEFORE_EXPORT_SHEET = 'beforeExportSheet';

    /**
     * 导出sheet之后
     */
    const EVENT_AFTER_EXPORT_SHEET = 'afterExportSheet';

    /**
     * 导出数据之前
     */
    const EVENT_BEFORE_EXPORT_DATA = 'beforeExportData';

    /**
     * 导出数据之后
     */
    const EVENT_AFTER_EXPORT_DATA = 'afterExportData';

    /**
     * 导出输出文件之前
     */
    const EVENT_BEFORE_EXPORT_OUTPUT = 'beforeExportOutput';

    /**
     * 导出输出文件之后
     */
    const EVENT_AFTER_EXPORT_OUTPUT = 'afterExportOutput';


    /**
     * 导入之前
     */
    const ENVENT_BEFORE_IMPORT = 'beforeImport';

    /**
     * 导入之后
     */
    const ENVENT_AFTER_IMPORT = 'afterImport';

    /**
     * 执行导入之前
     */

    const EVENT_BEFORE_IMPORT_EXCEL = 'beforeImportExcel';

    /**
     * 执行导入之后
     */
    const EVENT_AFTER_IMPORT_EXCEL = 'afterImportExcel';

    /**
     * 导入sheet之前
     */
    const EVENT_BEFORE_IMPORT_SHEET = 'beforeImportSheet';

    /**
     * 导入sheet之后
     */
    const EVENT_AFTER_IMPORT_SHEET = 'afterImportSheet';

    /**
     * 导入数据之前
     */
    const EVENT_BEFORE_IMPORT_DATA = 'beforeImportData';

    /**
     * 导入数据之后
     */
    const EVENT_AFTER_IMPORT_DATA = 'afterImportData';

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
     * 进度操作对象
     *
     * @var ExcelProgress
     */
    public $progress = [
        'class' => ExcelProgress::class,
    ];

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

        if (!$this->progress instanceof ExcelProgress) {
            $this->progress = Instance::ensure($this->progress, ExcelProgress::class);
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
     * @throws ExcelException
     * @throws \Throwable
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

            $this->trigger(static::ENVENT_BEFORE_EXPORT, $event);

            // 异步
            if ($config->getIsAsync()) {
                if ($config->getOutPutType() == ExportConfig::OUT_PUT_TYPE_OUT) {
                    throw new ExcelException('Async does not support output type ExportConfig::OUT_PUT_TYPE_OUT');
                }
                $exportData->queueId = $this->pushExportQueue($config->setToken($token));
                return $exportData;
            }

            $filePath = $this->exportExcel($config);

            // 文件输出
            $this->exportOutPut($config, $filePath, $exportData);

            $event->exportData = $exportData;

            $this->trigger(static::ENVENT_AFTER_EXPORT, $event);

            // 删除临时文件
            @$this->deleteFile($filePath);

        } catch (ExcelException $excelException) {
            $this->trigger(static::EVENT_ERROR, new ErrorEvent([
                'config' => $config,
                'exception' => $excelException,
            ]));
            throw $excelException;
        } catch (\Throwable $exception) {
            $this->trigger(static::EVENT_ERROR, new ErrorEvent([
                'config' => $config,
                'exception' => $exception,
            ]));
            \Yii::error($exception->getMessage(), 'excel/export');
            throw $exception;
        }

        return $exportData;
    }

    /**
     * 导入
     *
     * @param ImportConfig $config
     * @return void
     * @throws ExcelException
     * @throws \Throwable
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

            $this->trigger(static::ENVENT_BEFORE_IMPORT, $event);

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

            $this->trigger(static::ENVENT_AFTER_IMPORT, $event);
        } catch (ExcelException $excelException) {
            $this->trigger(static::EVENT_ERROR, new ErrorEvent([
                'config' => $config,
                'exception' => $excelException,
            ]));
            throw $excelException;
        } catch (\Throwable $exception) {
            $this->trigger(static::EVENT_ERROR, new ErrorEvent([
                'config' => $config,
                'exception' => $exception,
            ]));
            \Yii::error($exception->getMessage(), 'excel/import');
            throw $exception;
        }

        return $importData;
    }


    /**
     * 导入行回调
     *
     * @param callable $callback
     * @param ImportConfig $config
     * @param ImportSheet $sheet
     * @param array $row
     *
     * @return mixed|null
     */
    protected function importRowCallback(callable $callback, ImportConfig $config, ImportSheet $sheet, array $row)
    {
        $importRowCallbackParam = new ImportRowCallbackParam([
            'excel' => $this,
            'sheet' => $sheet,
            'importConfig' => $config,
            'row' => $row,
        ]);
        $event = new ImportDataEvent([
            'importRowCallbackParam' => $importRowCallbackParam,
            'row' => $row,
        ]);
        $this->trigger(static::EVENT_BEFORE_IMPORT_DATA, $event);

        try {
            $result = call_user_func($callback, $importRowCallbackParam);
        } catch (\Throwable $exception) {
            $event->isSuccess = false;
        }
        $this->trigger(static::EVENT_AFTER_IMPORT_DATA, $event);

        return $result ?? null;
    }


    /**
     * 导出数据回调
     *
     * @param callable $callback 回调
     * @param ExportConfig $config
     * @param ExportSheet $sheet
     * @param int $page 页码
     * @param int $pageSize 限制每页数量
     * @param int|null $totalCount
     * @return mixed
     */
    protected function exportDataCallback(callable $callback, ExportConfig $config, ExportSheet $sheet, int $page, int $pageSize, ?int $totalCount)
    {
        $exportCallbackParam = new ExportCallbackParam([
            'excel' => $this,
            'exportConfig' => $config,
            'sheet' => $sheet,

            'page' => $page,
            'pageSize' => $pageSize,
            'totalCount' => $totalCount,
        ]);

        $event = new ExportDataEvent([
            'exportCallbackParam' => $exportCallbackParam,
        ]);

        $this->trigger(static::EVENT_BEFORE_EXPORT_DATA, $event);

        // 执行回调
        $result = call_user_func($callback, $exportCallbackParam);

        $event->list = $result;

        $this->trigger(static::EVENT_AFTER_EXPORT_DATA, $event);

        return $result;
    }


    /**
     * 导出文件输出
     *
     * @param ExportConfig $config
     * @param string $filePath
     * @param ExportData $exportData
     * @return void
     * @throws ExcelException
     */
    protected function exportOutPut(ExportConfig $config, string $filePath, ExportData &$exportData)
    {
        $event = new ExportOutputEvent([
            'exportConfig' => $config,
        ]);
        $this->trigger(static::EVENT_BEFORE_EXPORT_OUTPUT, $event);
        switch ($config->outPutType) {
            // 上传
            case ExportConfig::OUT_PUT_TYPE_UPLOAD:
                if (!$this->fileSystem->writeStream($config->getPath(), fopen($filePath, 'r+'))) {
                    throw new ExcelException('upload file fail');
                }
                $url = $config->getUrl($config->getPath());
                $exportData->setPath($url);
                break;
            // 本地文件
            case ExportConfig::OUT_PUT_TYPE_LOCAL:
                if (copy($filePath, $config->getPath()) === false) {
                    throw new ExcelException('copy file fail');
                }
                $exportData->setPath($config->getPath());
                break;
            // 直接输出
            case ExportConfig::OUT_PUT_TYPE_OUT:
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
                break;
            default:
                throw new ExcelException('outPutType error');
                break;
        }
        $this->trigger(static::EVENT_AFTER_EXPORT_OUTPUT, $event);
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
        $config->setSheets(array_map(function ($sheet) {
            if (!$sheet instanceof \vartruexuan\excel\data\import\Sheet) {
                $sheet = new \vartruexuan\excel\data\import\Sheet($sheet);
            }
            return $sheet;
        }, $config->getSheets()));

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
}