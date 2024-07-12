# yii-excel

[![php](https://img.shields.io/badge/php-%3E=8.2-brightgreen.svg?maxAge=2592000)](https://github.com/php/php-src)
[![Latest Stable Version](https://img.shields.io/packagist/v/vartruexuan/yii-excel)](https://packagist.org/packages/vartruexuan/yii-excel)
[![Total Downloads](https://img.shields.io/packagist/dt/vartruexuan/yii-excel)](https://packagist.org/packages/vartruexuan/yii-excel)
[![License](https://img.shields.io/packagist/l/vartruexuan/yii-excel)](https://github.com/vartruexuan/yii-excel)

# 概述
excel 导入导出,相关文档处理组件

## 组件能力

- [x] 导入、导出excel
- [x] 支持异步操作,进度构建,信息构建
- [x] 支持 `xlswriter`
- [ ] 支持 `csv`
- [ ] ...
# 安装
```shell
composer require vartruexuan/yii-excel
```
## 配置

## 使用
### 配置
- 配置组件 `components`
```php
[
    'components' => [
        // excel组件
        'excel' => [
            'class' => \vartruexuan\excel\drivers\xlswriter\Excel::class,
            'fileSystem' => 'filesystem', // 文件管理组件
            'redis' => 'redis', // redis
            'queue' => 'queue', // 队列组件
            // 日志行为类
            'as log' => \vartruexuan\excel\behaviors\ExcelLogBehavior::class
        ]
    ]
]
```
### 导出
```php

```
### 导入

```php

```
## License

MIT
