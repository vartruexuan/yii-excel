# yii-excel

[![php](https://img.shields.io/badge/php-%3E=8.2-brightgreen.svg?maxAge=2592000)](https://github.com/php/php-src)
[![Latest Stable Version](https://img.shields.io/packagist/v/vartruexuan/yii-excel)](https://packagist.org/packages/vartruexuan/yii-excel)
[![Total Downloads](https://img.shields.io/packagist/dt/vartruexuan/yii-excel)](https://packagist.org/packages/vartruexuan/yii-excel)
[![License](https://img.shields.io/packagist/l/vartruexuan/yii-excel)](https://github.com/vartruexuan/yii-excel)

# 概述
excel 导入导出,支持异步、进度构建。

## 组件能力

- [x] 导入、导出excel
- [x] 支持异步操作,进度构建,进度消息输出
- [x] 格式 `xlsx` 
- [ ] ...


# 安装
- 安装依赖拓展 [xlswriter](https://xlswriter-docs.viest.me/zh-cn/an-zhuang)
```bash
pecl install xlswriter
```
- 安装组件
```shell
composer require vartruexuan/yii-excel
```
## 使用
### 配置
- 配置组件 `components`
```php
[
    'components' => [
        // excel组件
        'excel' => [
            'class' => \vartruexuan\excel\drivers\xlswriter\Excel::class,
            'fileSystem' => 'filesystem', // 文件管理组件(默认:filesystem)
            'redis' => 'redis',// redis组件(默认:redis)
            'queue' => 'queue', // 队列组件(默认:queue)
            // 进度组件
            'progress'=>[
                'class' => \vartruexuan\excel\ExcelProgress::class,
                'expireTime' => 3600, // 进度信息失效时长(秒)
                'prefix' => 'excel',// key前缀
                'redis' => 'redis',// redis组件(默认:redis)
                'queue' => 'queue', // 队列组件(默认:queue)
            ],
            // 进度行为
            'as progress' => \vartruexuan\excel\behaviors\ExcelProgressBehavior::class,
            // 日志行为
            'as log' => \vartruexuan\excel\behaviors\ExcelLogBehavior::class
        ]
    ]
]
```
- api
```php
// 导出
\Yii::$app->excel->export(\vartruexuan\excel\data\export\ExportConfig $config);
// 导入
\Yii::$app->excel->import(\vartruexuan\excel\data\export\ImportConfig $config);
// 进度查询
\Yii::$app->excel->progress->getProgressRecord($token,true);
// 进度消息输出查询
\Yii::$app->excel->progress->getProgressMessage($token);
```
### 导出
```php
/**
 * 导出配置
 * 
 * @var \vartruexuan\excel\data\export\ExportConfig $config 
 */
$config = new DemoExportConfig([
    // 额外参数,比如导出需要筛选时
    'param' => [
         'name' => '先生',
     ]   
]);
/**
 *  导出数据 
 * 
 * @var  \vartruexuan\excel\data\export\ExportData $exportData 
 */
$exportData = \Yii::$app->excel->export($config);
$token = $exportData->token; // 导出唯一标识
```
- 配置

```php
<?php

namespace common\excel\export;

use vartruexuan\excel\data\export\Column;
use vartruexuan\excel\data\export\ExportCallbackParam;
use vartruexuan\excel\data\export\ExportConfig;
use vartruexuan\excel\data\export\Sheet;


/**
 * 用户分组导出
 */
class DemoExportConfig extends ExportConfig
{
    /**
     *  是否异步导出
     *    true 推入队列中异步消费
     *    false 同步导出   
     * 
     * @var bool 
     */
    public bool $isAsync = true;
    
    /**
     *  输出类型
     *    OUT_PUT_TYPE_UPLOAD  上传 filesystem  
     *    OUT_PUT_TYPE_LOCAL   保存到本地         
     *    OUT_PUT_TYPE_OUT     直接输出          
     * @var string 
     */
    public string $outPutType = self::OUT_PUT_TYPE_LOCAL;

    /**
     * 页配置
     *
     * @return array|Sheet[]
     * @throws \Exception
     */
    public function getSheets()
    {
        $this->setSheets([
            // 可配置多页
            new Sheet([
                'name' => 'sheet1',
                'columns' => [
                    new Column([
                        'title' => '姓名',
                        'field' => 'name',
                    ]),
                    new Column([
                        'title' => '年龄',
                        'field' => 'age',
                    ]),
                    new Column([
                        'title' => '身高',
                        'field' => 'height',
                    ]),
                    new Column([
                        'title' => '职业',
                        'field' => 'post',
                    ]),
                ],
                'count' => $this->dataCount(),
                // 1.直接数组数据 2.支持回调分批导出
                'data' => [$this, 'dataCallback'],
                 // 每页导出数量 
                'pageSize' => 500,
            ])
        ]);
        return $this->sheets;
    }

    /**
     * 数据回调
     *
     * @param ExportCallbackParam $exportCallbackParam
     * @return array|array[]
     * @throws RandomException
     */
    public function dataCallback(ExportCallbackParam $exportCallbackParam)
    {
        // $exportCallbackParam->page  当前页
        // $exportCallbackParam->pageSize 限制每页数量
        // $exportCallbackParam->totalCount 数据总数
    
        // 额外参数
        $exportCallbackParam->exportConfig->getParam();
        
        $list = [];
        for ($i = 0; $i < $exportCallbackParam->pageSize; $i++) {
            $list[] = [
                'name' => '先生' . random_int(1, 100000),
                'age' => random_int(10, 100),
                'height' => random_int(100, 180),
                'post' => '无业',
            ];
        }
        return $list;
    }

    /**
     * 数据数量
     *
     * @return array|array[]|int
     * @throws \Exception
     */
    public function dataCount()
    {
        return 1000;
    }
    
    
      /**
       *  设置保存地址
       * 
       * @return string
       */
    public function getPath()
    {
         // 上传  excel/demo.xlsx
         // 本地  /www/project/upload/demo.xlsx
         // 直接输出 demo.xlsx
         return  '/excels/demo_'. date('Y_m_d_H_i_s_u') . '.xlsx';
    }
}

```

### 导入

```php

/**
 * 导入配置
 * 
 * @var  \vartruexuan\excel\data\import\ImportConfig $config  
 */
$config = new DemoImportConfig([
        // 导入文件地址
       'path' => '/www/files/ceshi.xlsx', // 本地地址
       // 'path' => 'https://xxx.com/ceshi.xlsx', // 远程地址
]);
/**
 * 导入
 * 
 * @var  \vartruexuan\excel\data\import\ImportData $importData 
 */
$importData = \Yii::$app->excel->import($config);
$token = $exportData->token; // 导入唯一标识

```
- 配置
```php

<?php

namespace common\excel\import;

use vartruexuan\excel\data\import\ImportConfig;
use vartruexuan\excel\data\import\ImportRowCallbackParam;
use vartruexuan\excel\data\import\Sheet;
use vartruexuan\excel\ExcelAbstract;
use vartruexuan\excel\exceptions\ExcelException;

/**
 * 用户组导入
 */
class DemoImportConfig extends ImportConfig
{
    /**
     *  是否异步导入
     *    true 推入队列中异步消费
     *    false 同步导入  
     * 
     * @var bool 
     */
    public bool $isAsync = true;

    /** 
     *  页配置
     * 
     * @return array
     */
    public function getSheets(): array
    {
        $this->setSheets([
            new Sheet([
                'name' => 'sheet1',
                'isReturnSheetData' => true,
                'isSetHeader' => true,
                // 列名映射
                'headerMap' => [
                    '姓名' => 'name',
                    '年龄' => 'age',
                ],
                // 行数据回调
                'callback' => [$this, 'rowCallback']
            ])
        ]);
        return $this->sheets;
    }


    /**
     * 行处理
     *
     * @param ImportRowCallbackParam $importRowCallbackParam  行数据
     * @return void
     * @throws ExcelException
     * @throws \Throwable
     */
    public function rowCallback(ImportRowCallbackParam $importRowCallbackParam)
    {
        try {
            
            // 当前行数据  $importRowCallbackParam->row
            
            
            //todo 执行业务代码

        } catch (ExcelException $excelException) {
            // 设置进度消息
            $importRowCallbackParam->excel->progress->pushProgressMessage($importRowCallbackParam->importConfig->getToken(), $excelException->getMessage());
            throw $e;
        } catch (\Throwable $exception) {
            $importRowCallbackParam->excel->progress->pushProgressMessage($importRowCallbackParam->importConfig->getToken(), "导出异常");
            \Yii::error('导出demo异常:'.$exception->getMessage());
            throw $exception;
        }
    }
}

```
### 进度
- 进度查询
```php
/**
 * 进度信息
 *      token 导入/导出时返回的token
 * @var \vartruexuan\excel\data\progress\ProgressRecord $progressRecord
 */
$progressRecord = \Yii::$app->excel->progress->getProgressRecord($token,true);
// $progressRecord->progress 总进度信息
// $progressRecord->progress->status 进度状态 1.待处理 2.处理中 3.完成 4.失败 
// $progressRecord->sheetListProgress 页码进度信息
```
- 消息输出查询
```php
/**
 *  token 导入/导出时返回的token
 * 
 * @var array|null $messages
 */
$messages = Yii::$app->excel->progress->getProgressMessage($token,30);
```
## License

MIT
