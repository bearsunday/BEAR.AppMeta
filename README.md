# BEAR.AppMeta

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bearsunday/BEAR.AppMeta/badges/quality-score.png?b=1.x)](https://scrutinizer-ci.com/g/bearsunday/BEAR.AppMeta/?branch=1.x)
[![Code Coverage](https://scrutinizer-ci.com/g/bearsunday/BEAR.AppMeta/badges/coverage.png?b=1.x)](https://scrutinizer-ci.com/g/bearsunday/BEAR.AppMeta/?branch=1.x)
[![Build Status](https://travis-ci.org/bearsunday/BEAR.AppMeta.svg?branch=1.x)](https://travis-ci.org/bearsunday/BEAR.AppMeta)

Application meta data value object

 * AppMeta object keep the application path such as `$tmpDir`, `$logDir` and `$appDir` in public property by given app name and context.

 * `getResourceListGenerator()` return `\Generator` to get resource class name and paths.


```php
use BEAR\AppMeta\AppMeta;

$appMeta = new AppMeta('MyVendor\HelloWorld');

// provids directory path

// $appMeta->name;    // MyVendor\HelloWorld
// $appMeta->appDir;  // MyVendor\HelloWorld/src
// $appMeta->logDir;  // MyVendor\HelloWorld/var/log
// $appMeta->tmpDir;  // MyVendor\HelloWorld/var/tmp

// resource class / list generator

foreach ($appMeta->getResourceListGenerator() as list($class, $file)) {
    var_dump($class); // FakeVendor\HelloWorld\Resource\App\Greeting
    var_dump($file);  // path/to/Greeting.php
}
```
