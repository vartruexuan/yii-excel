<?php

namespace vartruexuan\excel\data\import;


use yii\base\BaseObject;

/**
 * 导入数据
 */
class ImportData extends BaseObject
{

    /**
     * 导入token
     *
     * @var string
     */
    public string $token;

    /**
     * 页码返回数据
     *
     * @var
     */
    public $sheetDatas;

    /**
     * 队列ID
     *
     * @var mixed
     */
    public $queueId;


    /**
     * 页码数据
     *
     * @param $sheetData
     * @param $sheetName
     * @return ImportData
     */
    public function addSheetData($sheetData, $sheetName = 'sheet1')
    {
        $this->sheetDatas[strtolower($sheetName)] = $sheetData;
        return $this;
    }

    /**
     * 获取页数据
     *
     * @param $sheetName
     * @return mixed
     */
    public function getSheetData($sheetName = 'sheet1')
    {
        return $this->getSheetData(strtolower($sheetName));
    }

}