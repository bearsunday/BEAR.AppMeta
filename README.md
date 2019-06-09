# BEAR.AppMeta

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bearsunday/BEAR.AppMeta/badges/quality-score.png?b=1.x)](https://scrutinizer-ci.com/g/bearsunday/BEAR.AppMeta/?branch=1.x)
[![Code Coverage](https://scrutinizer-ci.com/g/bearsunday/BEAR.AppMeta/badges/coverage.png?b=1.x)](https://scrutinizer-ci.com/g/bearsunday/BEAR.AppMeta/?branch=1.x)
[![Build Status](https://travis-ci.org/bearsunday/BEAR.AppMeta.svg?branch=1.x)](https://travis-ci.org/bearsunday/BEAR.AppMeta)

Application meta data value object

 * AppMeta object keep the application path such as `$tmpDir`, `$logDir` and `$appDir` in public property by given app name and context.

 * `getGenerator()` return `\Generator` to get resource meta data.


```php
use BEAR\AppMeta\AppMeta;

$appMeta = new AppMeta('MyVendor\HelloWorld');

// provids directory path

// $appMeta->name;    // MyVendor\HelloWorld
// $appMeta->appDir;  // MyVendor\HelloWorld/src
// $appMeta->logDir;  // MyVendor\HelloWorld/var/log
// $appMeta->tmpDir;  // MyVendor\HelloWorld/var/tmp

// resource meta generator

foreach ($appMeta->getGenerator('*') as $resourceMeta) {
    var_dump($resourceMeta->uriPath); // app://self/one
    var_dump($resourceMeta->class);   // FakeVendor\HelloWorld\Resource\App\One
    var_dump($resourceMeta->file);    // /path/to/src/Resource/App/One.php
}

foreach ($appMeta->getGenerator('app') as $resourceMeta) {
    var_dump($resourceMeta->uriPath); // /one
    var_dump($resourceMeta->class);   // FakeVendor\HelloWorld\Resource\App\One
    var_dump($resourceMeta->file);    // /path/to/src/Resource/App/One.php
}
```
