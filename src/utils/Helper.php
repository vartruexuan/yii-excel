<?php

namespace vartruexuan\excel\utils;

use Overtrue\Http\Client;
use Ramsey\Uuid\Uuid;
use yii\base\BaseObject;
use yii\base\StaticInstanceTrait;
use yii\helpers\FileHelper;

class Helper extends BaseObject
{
    use StaticInstanceTrait;


    /**
     * 下载文件
     *
     * @param string $remotePath 远程地址
     * @param string $filePath
     * @return string|false
     */
    public function downloadFile(string $remotePath, string $filePath)
    {
        $response = Client::create([
            'response_type' => 'raw',
        ])->request($remotePath, 'GET', [
            'verify' => false,
            'http_errors' => false,
        ]);

        if (@file_put_contents($filePath, $response->getBody()->getContents())) {
            return $filePath;
        }
        return false;
    }


    /**
     * 是否是远程地址
     *
     * @param $url
     * @return false|int
     */
    public function isUrl($url)
    {
        return preg_match('/^http[s]?:\/\//', $url);
    }


    /**
     * 获取uuid4
     *
     * @return void
     */
    public function uuid4()
    {
        return Uuid::uuid4()->getHex()->toString();
    }

    /**
     * 删除文件
     *
     * @param string $filePath
     * @return bool
     */
    public function deleteFile(string $filePath)
    {
        return FileHelper::unlink($filepath);
    }

}