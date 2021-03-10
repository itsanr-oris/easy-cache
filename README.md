## 简介

基于symfony/cache组件基础上搭建的简单缓存组件

[![Build Status](https://travis-ci.com/itsanr-oris/easy-cache.svg?branch=master)](https://travis-ci.com/itsanr-oris/easy-cache)
[![codecov](https://codecov.io/gh/itsanr-oris/easy-cache/branch/master/graph/badge.svg?token=b5M4CKN8j1)](https://codecov.io/gh/itsanr-oris/easy-cache)
[![Latest Stable Version](https://poser.pugx.org/f-oris/easy-cache/v)](//packagist.org/packages/f-oris/easy-cache)
[![Total Downloads](https://poser.pugx.org/f-oris/easy-cache/downloads)](//packagist.org/packages/f-oris/easy-cache)
[![Latest Unstable Version](https://poser.pugx.org/f-oris/easy-cache/v/unstable)](//packagist.org/packages/f-oris/easy-cache)
[![License](https://poser.pugx.org/f-oris/easy-cache/license)](//packagist.org/packages/f-oris/easy-cache)

## 版本说明

|  版本 | php | 备注  |
|  ---  | ---- | ---- |
| < 1.0 | >= 5.5 | 非正式版本，后续移除 |
| < 1.1 | >= 7.1 | 正式版本 |
| >= 1.1 | >= 5.5 | 正式版本 |

当前最新版本为 `1.1` , 主要变如下：

- [x] 移除`php 7.1`版本限制，更改为`php 5.5`
- [x] 废弃`Factory`类中`alais`、`getDriverConfig`方法，后续大版本更新时移除
- [x] 修复使用`file`缓存驱动时，读取不到缓存配置异常
- [x] 在`Cache`类中增加`extend`方法，用于快速扩展缓存驱动
- [x] 优化`Cache`类`__construct`函数代码逻辑，支持多种类型参数传入

## 安装

通过composer引入扩展包

```bash
composer require f-oris/easy-cache:^1.1
```

## 配置

参考`config.example.php`文件

## 基础用法

#### 1. 设置缓存

```php
<?php

use Foris\Easy\Cache\Cache;

$config = [
    // 缓存配置
];
$cache = new Cache($config);

/**
 * 设置单个缓存
 * 
 * 注：set方法等同于put方法
 */
$cache->set('key', 'value');
$cache->put('key', 'value', 3600);

/**
 * 设置多个缓存
 * 
 * 注：等价于调用了多次put，分别设置key_1，key_2，缓存时间为3600秒
 */
$cache->putMany(['key_1' => 'value_1', 'key_2' => 'value_2'], 3600);
```

#### 2. 判断缓存是否存在

```php
<?php

use Foris\Easy\Cache\Cache;

$config = [
    // 缓存配置
];
$cache = new Cache($config);

/**
 * 缓存存在时，返回true, 不存在时，返回false.
 */
$cache->has('key');

```

#### 3. 获取缓存

```php
<?php

use Foris\Easy\Cache\Cache;

$config = [
    // 缓存配置
];
$cache = new Cache($config);

/**
 * 获取缓存结果
 * 
 * 注：获取不到缓存的情况下，返回null
 */
$cache->get('key');
```

#### 4. 通过闭包设置并获取缓存

```php
<?php

use Foris\Easy\Cache\Cache;

$config = [
    // 缓存配置
];
$cache = new Cache($config);

/**
 * 通过闭包函数设置缓存
 * 
 * 注：
 * 1. 等价于将闭包函数的运行结果缓存到key中，缓存时间为3600秒
 * 2. 缓存没命中的情况下，会执行闭包函数，写入缓存，并返回执行结果，缓存命中的情况下，直接返回缓存结果
 */
$cache->remember('key', 3600, function () {
    return 'value';
});
```

#### 5. 删除缓存

```php
<?php

use Foris\Easy\Cache\Cache;

$config = [
    // 缓存配置
];
$cache = new Cache($config);

/**
 * 删除指定缓存
 *
 * 注：forget等价于delete 
 */
$cache->forget('key');
$cache->delete('key');

/**
 * 清除所有缓存
 * 
 * 注：flush等价于clear
 */
$cache->flush();
$cache->clear();
```

#### 6. 扩展自定义缓存驱动

```php
<?php

class MyFilesystemAdapter extends \Symfony\Component\Cache\Adapter\FilesystemAdapter
{
    
}

$callback = function (array $config = []) {
    return new MyFilesystemAdapter($config['namespace'], $config['lifetime'], $config['path']);
};

$factory = new \Foris\Easy\Cache\Factory();
$factory->extend($callback, 'my-file');

$config = [
    // ...
    'drivers' => [
        // ...
        'my-file' => [
            'namespace' => 'my_file',
            'lifetime' => 1800,
            'path' => sys_get_temp_dir() . '/my-cache/',  
        ]
    ]
];

$cache = new \Foris\Easy\Cache\Cache($factory, $config);
$cache->driver('my-file')->set('key', 'value');

```

> 扩展的缓存驱动需要按照psr-6规范实现相应的缓存接口

## License

MIT License

Copyright (c) 2019-present F.oris <us@f-oris.me>
