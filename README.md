# BEAR.AppMeta

BEAR.Sunday application meta information object

```php
use BEAR\AppMeta\AppMeta;

$appMeta = new AppMeta('MyVendor\HelloWorld');
// $appMeta->name;    // MyVendor\HelloWorld
// $appMeta->appDir;  // MyVendor\HelloWorld/src
// $appMeta->logDir;  // MyVendor\HelloWorld/var/log
// $appMeta->tmpDir;  // MyVendor\HelloWorld/var/tmp

foreach ($appMeta->getResourceListGenerator() as list($class, $file)) {
    var_dump($class); // FakeVendor\HelloWorld\Resource\App\Greeting
    var_dump($file);  // path/to/Greeting.php
}
```
## Requirements

 * bear/package ~1.0
