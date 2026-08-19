# BEAR.AppMeta

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bearsunday/BEAR.AppMeta/badges/quality-score.png?b=1.x)](https://scrutinizer-ci.com/g/bearsunday/BEAR.AppMeta/?branch=1.x)
[![Code Coverage](https://scrutinizer-ci.com/g/bearsunday/BEAR.AppMeta/badges/coverage.png?b=1.x)](https://scrutinizer-ci.com/g/bearsunday/BEAR.AppMeta/?branch=1.x)
[![Build Status](https://travis-ci.org/bearsunday/BEAR.AppMeta.svg?branch=1.x)](https://travis-ci.org/bearsunday/BEAR.AppMeta)

**BEAR.AppMeta** is a lightweight library for managing application metadata in PHP. It provides a simple way to access application directory paths and resource metadata, making it easier to organize and retrieve essential information about your application.

---

## Features

- **Application Metadata Management**: Retrieve directory paths such as `appDir`, `logDir`, and `tmpDir` with ease.
- **Resource Metadata Generator**: Use a generator to efficiently fetch metadata for resources in your application.

---
## Usage

### Accessing Application Metadata

The `Meta` class provides access to application metadata, including directory paths:

```php
use BEAR\AppMeta\Meta;

// Initialize with your application's namespace
$appMeta = new Meta('MyVendor\HelloWorld');

// Access metadata properties
echo $appMeta->name;     // MyVendor\HelloWorld
echo $appMeta->appDir;   // /path/to/project
echo $appMeta->logDir;   // /path/to/project/var/log/{context}
echo $appMeta->tmpDir;   // /path/to/project/var/tmp/{context}
echo $appMeta->buildDir; // /path/to/project/var/build/{context}

// Optional path overrides (null keeps the defaults above):
// new Meta($name, $context, $appDir, tmpDir: '/var/tmp/my-app', logDir: '/var/log/my-app');

// An application that may not write in its own directory - a read-only image, an archive -
// is placed under a writable base:
$appMeta = Meta::create('MyVendor\HelloWorld', 'prod-app', $appMeta->appDir, '/mnt/write');
echo $appMeta->tmpDir;    // /mnt/write/MyVendor/HelloWorld/prod-app/tmp

// Environment reading belongs to the application, not Meta.
```

### Fetching Resource Metadata

Use the `getGenerator()` method to retrieve metadata for resources. This method returns a generator, making it memory-efficient for large applications.

```php
// Fetch metadata for all resources
foreach ($appMeta->getGenerator('*') as $resourceMeta) {
    var_dump($resourceMeta->uriPath); // app://self/one
    var_dump($resourceMeta->class);   // FakeVendor\HelloWorld\Resource\App\One
    var_dump($resourceMeta->file);    // /path/to/src/Resource/App/One.php
}

// Fetch metadata for resources in the 'app' namespace
foreach ($appMeta->getGenerator('app') as $resourceMeta) {
    var_dump($resourceMeta->uriPath); // /one
    var_dump($resourceMeta->class);   // FakeVendor\HelloWorld\Resource\App\One
    var_dump($resourceMeta->file);    // /path/to/src/Resource/App/One.php
}
```
