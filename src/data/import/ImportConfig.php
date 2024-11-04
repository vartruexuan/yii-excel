<?php

namespace vartruexuan\excel\data\import;


use vartruexuan\excel\ExcelAbstract;
use vartruexuan\excel\exceptions\ExcelException;
use vartruexuan\excel\utils\Helper;
use yii\base\BaseObject;
use yii\base\Model;
use yii\helpers\FileHelper;

/**
 * 导入配置对象
 */
class ImportConfig extends Model
{
    /**
     * 导入地址
     *
     * @var string
     */
    public string $path = '';


    /**
     * 是否异步
     *
     * @var bool
     */
    public bool $isAsync = true;

    /**
     * 异步条件时设置对应token
     *
     * @var string
     */
    public string $token = '';

    /**
     * 读取页
     * @var Sheet[]
     */
    public array $sheets = [];

    /**
     * 临时文件地址
     *
     * @var string
     */
    private string $tempPath = '';


    /**
     * 获取页配置
     *
     * @return array
     */
    public function getSheets(): array
    {
        return $this->sheets;
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


    /**
     * 获取token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }


    /**
     * 是否异步
     *
     * @return bool
     */
    public function getIsAsync(): bool
    {
        return $this->isAsync;
    }

    /**
     * 设置页码信息
     *
     * @param array $sheets
     * @return $this
     */
    public function setSheets(array $sheets)
    {
        $this->sheets = $sheets;
        return $this;
    }


    /**
     * 添加读取页
     *
     * @param Sheet $sheet
     * @return ImportConfig
     */
    public function addSheet(Sheet $sheet)
    {
        $this->sheets[] = $sheet;
        return $this;
    }

    /**
     * 设置导入地址
     *
     * @param string $path
     * @return $this
     */
    public function setPath(string $path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * 设置token
     *
     * @return ImportConfig
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * 设置临时文件地址
     *
     * @param string $tempPath
     * @return $this
     */
    final public function setTempPath(string $tempPath)
    {
        $this->tempPath = $tempPath;
        return $this;
    }

    /**
     * 获取临时文件地址
     *
     * @return string
     */
    final public function getTempPath(): string
    {
        return $this->tempPath;
    }


    /**
     * 序列化
     *
     * @return array
     */
    public function __serialize(): array
    {
        return [
            'path' => $this->getPath(),
            'isAsync' => $this->isAsync,
            'token' => $this->getToken(),
        ];
    }
}