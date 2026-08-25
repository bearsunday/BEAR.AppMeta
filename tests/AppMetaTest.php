<?php

declare(strict_types=1);

namespace BEAR\AppMeta;

use BEAR\AppMeta\Exception\AppNameException;
use BEAR\AppMeta\Exception\NotWritableException;
use FakeVendor\HelloWorld\Resource\App\One;
use PHPUnit\Framework\TestCase;

use function chmod;
use function dirname;
use function file_put_contents;
use function str_replace;

use const DIRECTORY_SEPARATOR;
use const PHP_OS_FAMILY;

class AppMetaTest extends TestCase
{
    /** @var Meta */
    protected $appMeta;

    protected function setUp(): void
    {
        parent::setUp();

        $app = dirname(__DIR__) . '/tests/Fake/fake-app/var/tmp';
        file_put_contents($app . '/app/cache', '1');
        // ディレクトリの権限を元に戻しておく
        chmod(__DIR__ . '/Fake/fake-not-writable/var', 0644);
        $this->appMeta = new Meta('FakeVendor\HelloWorld', 'prod-app');
    }

    protected function tearDown(): void
    {
        $notWritableDir = __DIR__ . '/Fake/fake-not-writable/var';
        if (PHP_OS_FAMILY === 'Windows') {
            @chmod($notWritableDir, 0777);
        } else {
            chmod($notWritableDir, 0777);
        }
    }

    public function testNew(): void
    {
        $actual = $this->appMeta;
        $this->assertInstanceOf(Meta::class, $actual);
        $this->assertSame($actual->appDir . '/var/tmp/prod-app', $actual->tmpDir);
    }

    public function testInvalidName(): void
    {
        $this->expectException(AppNameException::class);
        new Meta('Invalid\Invalid');
    }

    public function testNotWritable(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Skipping write-protected test on Windows.');
        }

        $this->expectException(NotWritableException::class);
        new AppMeta('FakeVendor\NotWritable');
    }

    public function testDoNotClear(): void
    {
        new Meta('FakeVendor\HelloWorld', 'test-app');
        $this->assertFileExists(__DIR__ . '/Fake/fake-app/var/tmp/test-app/not-cleared.txt');
    }

    public function testGetGeneratorApp(): void
    {
        $appMeta = new Meta('FakeVendor\HelloWorld');
        $uris = [];
        $paths = [];
        foreach ($appMeta->getGenerator('app') as $uri) {
            $uris[] = $uri;
            $paths[] = $uri->uriPath;
        }

        $this->assertCount(5, $uris);
        $this->assertSame('/one', $uris[0]->uriPath);
        $this->assertSame(One::class, $uris[0]->class);
        $this->assertStringContainsString($this->normalizePath('src/Resource/App/One.php'), $this->normalizePath($uris[0]->filePath));
        $this->assertSame('/one', $paths[0]);
    }

    public function testGetGeneratorAll(): void
    {
        $appMeta = new Meta('FakeVendor\HelloWorld');
        $uris = [];
        foreach ($appMeta->getGenerator('*') as $uri) {
            $uris[] = $uri;
        }

        $this->assertCount(6, $uris);
        $this->assertSame('app://self/one', $uris[0]->uriPath);
        $this->assertSame(One::class, $uris[0]->class);
        $this->assertStringContainsString($this->normalizePath('src/Resource/App/One.php'), $this->normalizePath($uris[0]->filePath));
    }

    public function normalizePath(string $path): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }
}
