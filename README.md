<h1 align="center"> laravel-cos-sts </h1>

<p align="center"> 腾讯云COS STS授权的PHP SDK for Laravel.</p>


## Installing

```shell
$ composer require buxuhunao/laravel-cos-sts -vvv
```

## Configuration

Add a new disk to your `config/filesystems.php` config:
```php
<?php

return [
   'disks' => [
       //...
       'cos' => [
           'driver' => 'cos',
           
           'app_id'     => env('COS_APP_ID'),
           'secret_id'  => env('COS_SECRET_ID'),
           'secret_key' => env('COS_SECRET_KEY'),
           'region'     => env('COS_REGION', 'ap-guangzhou'),
           
           'bucket'     => env('COS_BUCKET'),  // 不带数字 app_id 后缀
           'cdn'        => env('COS_CDN'),
           'signed_url' => false,
           
           'prefix' => env('COS_PATH_PREFIX'), // 全局路径前缀
           
           'guzzle' => [
               'timeout' => env('COS_TIMEOUT', 60),
               'connect_timeout' => env('COS_CONNECT_TIMEOUT', 60),
           ],
       ],
       //...
    ]
];
```

## Usage

```php
// 设置临时密钥有效期，单位为秒，不设置默认为30分钟
setDurationSeconds(int $seconds) 

// 策略允许/拒绝，默认为true允许
setEffect(bool $isAllow)

// 设置策略和对应的资源，多个策略可多次调用
setPolicy(string|array $allowActions, string|array $allowPrefixes)

// 获取临时密钥
getTempKeys(array $options) 

// 用法1：
$config = [
    'allowActions' => ['name/cos:PutObject', 'name/cos:PostObject'], // 字符串或数组
    'allowPrefixes' => ['docs/*', '/xls/*', 'ppt/a.ppt'], // 字符串或数组
    'durationSeconds' => 3600, // 可选，默认1800
];

return Sts::getTempKeys($config);

// 用法2：
Sts::setDurationSeconds(3600)
    ->setPolicy(['name/cos:PutObject', 'name/cos:PostObject'], ['docs/*', '/xls/*', 'ppt/a.ppt'])
    ->getTempKeys();
```

## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/buxuhunao/laravel-cos-sts/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/buxuhunao/laravel-cos-sts/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## License

MIT