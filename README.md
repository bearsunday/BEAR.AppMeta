# BEAR.AppMeta

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bearsunday/BEAR.AppMeta/badges/quality-score.png?b=1.x)](https://scrutinizer-ci.com/g/bearsunday/BEAR.AppMeta/?branch=1.x)
[![Code Coverage](https://scrutinizer-ci.com/g/bearsunday/BEAR.AppMeta/badges/coverage.png?b=1.x)](https://scrutinizer-ci.com/g/bearsunday/BEAR.AppMeta/?branch=1.x)
[![Build Status](https://travis-ci.org/bearsunday/BEAR.AppMeta.svg?branch=1.x)](https://travis-ci.org/bearsunday/BEAR.AppMeta)

**BEAR.AppMeta** is a lightweight library for managing application metadata in PHP. It provides a simple way to access application directory paths and resource metadata, making it easier to organize and retrieve essential information about your application.

---

## Features

- **Application Metadata Management**: Retrieve directory paths such as `appDir`, `logDir`, `tmpDir`, and `buildDir` with ease.
- **Resource Metadata Generator**: Use a generator to efficiently fetch metadata for resources in your application.

---
## Usage

### Accessing Application Metadata

```php
use BEAR\AppMeta\Meta;

$appMeta = new Meta('MyVendor\HelloWorld');
```

- `name` — the application namespace (`MyVendor\HelloWorld`)
- `appDir` — the application directory
- `tmpDir` — data the running application writes
- `logDir` — log files
- `buildDir` — artifacts a build generates once and every run reads (compiled DI scripts, templates)

Pass `$appDir`, `$tmpDir` or `$logDir` to the constructor to override:

```php
$appMeta = new Meta('MyVendor\HelloWorld', 'prod-app', $appDir, '/mnt/write/tmp', '/mnt/write/log');
```

A `Meta` says where the application writes; it creates nothing, so constructing one against a read-only tree or a phar is fine. Whoever writes to a directory creates it.

Environment reading belongs to the application, not Meta.

### Libraries: take paths from the injected Meta

Do not bind a path read from a Meta into compiled or cached code - the artifact may run somewhere else. Inject `AbstractAppMeta` and read paths at runtime; a restored Meta re-points them to the current location.

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
