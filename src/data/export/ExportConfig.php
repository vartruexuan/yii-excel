<?php

namespace vartruexuan\excel\data\export;

use yii\base\BaseObject;

/**
 * 导出配置
 */
class ExportConfig extends BaseObject
{
    // 导出操作类型
    public const OUT_PUT_TYPE_UPLOAD = 'upload'; // 上传第三方
    public const OUT_PUT_TYPE_LOCAL = 'local'; // 保存到本地
    public const OUT_PUT_TYPE_OUT = 'out'; // 直接输出

    /**
     * 页配置
     *
     * @var Sheet[]
     */
    public array $sheets = [];

    /**
     * 保存地址（local/upload）
     *
     * @var string
     */
    public string $path = '';

    /**
     * 是否异步导出
     *
     * @var bool
     */
    public bool $isAsync = false;

    /**
     * 输出类型
     *
     * @var string
     */
    public string $outPutType = self::OUT_PUT_TYPE_OUT;


    /**
     * token
     *
     * @var string
     */
    public ?string $token = null;


    /**
     * 额外参数（数据回调中需要使用）
     *
     * @var null
     */
    public $param = null;


    /**
     * 设置驱动
     *
     * @var null
     */
    public $driverClass = null;

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
        return $this->sheets;
    }

    /**
     * 设置页
     *
     * @param $sheets
     * @return $this
     */
    public function setSheets($sheets)
    {
        $this->sheets = $sheets;
        return $this;
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

    /**
     * 获取文件名
     *
     * @return string
     */
    public function getFileName()
    {
        return basename($this->getPath());
    }


    /**
     * 获取导出参数
     *
     * @return null
     */
    public function getParam()
    {
        return $this->param;
    }

    /**
     * 是否异步
     *
     * @return bool
     */
    public function getIsAsync()
    {
        return $this->isAsync;
    }

    /**
     * 获取token
     *
     * @return void
     */
    public function getToken()
    {
        return $this->token;
    }


    /**
     * 设置token
     *
     * @param $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * 获取所有页名
     *
     * @return void
     */
    public function getSheetNames()
    {
        return array_map(function (Sheet $n) {
            return $n->name;
        }, $this->getSheets());
    }


    /**
     * 获取文件完整地址
     *
     * @param $path
     * @return string
     */
    abstract function getUrl($path): string;

    /**
     * 序列化
     *
     * @return array
     */
    public function __serialize(): array
    {
        return [
            'token' => $this->getToken(),
            'path' => $this->getPath(),
            'isAsync' => $this->getIsAsync(),
            'outPutType' => $this->getOutPutType(),
            'param' => $this->getParam(),
        ];
    }

}