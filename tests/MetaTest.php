<?php

declare(strict_types=1);

namespace BEAR\AppMeta;

use BEAR\AppMeta\Exception\AppNameException;
use FakeVendor\HelloWorld\Resource\App\One;
use FakeVendor\HelloWorld\Resource\App\Sub\Sub\Four;
use FakeVendor\HelloWorld\Resource\App\Sub\Three;
use FakeVendor\HelloWorld\Resource\App\Two;
use FakeVendor\HelloWorld\Resource\App\User;
use FakeVendor\HelloWorld\Resource\Page\Index;
use PHPUnit\Framework\TestCase;

use function dirname;
use function file_put_contents;
use function sort;
use function str_replace;
use function sys_get_temp_dir;
use function uniqid;

use const DIRECTORY_SEPARATOR;

class MetaTest extends TestCase
{
    /** @var Meta */
    protected $meta;

    protected function setUp(): void
    {
        parent::setUp();

        $app = $this->normalizePath(dirname(__DIR__) . '/tests/Fake/fake-app/var/tmp');
        file_put_contents(
            $app . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'cache',
            '1',
        );
        $this->meta = new Meta('FakeVendor\HelloWorld', 'prod-app');
    }

    public function testAppReflectorResourceList(): void
    {
        $meta = new Meta('FakeVendor\HelloWorld');
        $classes = $files = [];
        foreach ($meta->getResourceListGenerator() as [$class, $file]) {
            $classes[] = $class;
            $files[] = $this->normalizePath($file);
        }

        $expect = [
            One::class,
            Two::class,
            User::class,
            Index::class,
            Three::class,
            Four::class,
        ];
        sort($expect);
        sort($classes);
        $this->assertSame($expect, $classes);

        // ファイルパスの比較は相対パスで行う
        $expectFiles = [
            $this->normalizePath($this->meta->appDir . '/src/Resource/App/One.php'),
            $this->normalizePath($this->meta->appDir . '/src/Resource/App/Two.php'),
            $this->normalizePath($this->meta->appDir . '/src/Resource/App/User.php'),
            $this->normalizePath($this->meta->appDir . '/src/Resource/Page/Index.php'),
            $this->normalizePath($this->meta->appDir . '/src/Resource/App/Sub/Three.php'),
            $this->normalizePath($this->meta->appDir . '/src/Resource/App/Sub/Sub/Four.php'),
        ];
        sort($expectFiles);
        sort($files);
        $this->assertSame($expectFiles, $files);
    }

    public function testVarTmpFolderCreation(): void
    {
        new Meta('FakeVendor\HelloWorld', 'stage-app');
        $this->assertFileExists($this->normalizePath(__DIR__ . '/Fake/fake-app/var/log/stage-app'));
        $this->assertFileExists($this->normalizePath(__DIR__ . '/Fake/fake-app/var/tmp/stage-app'));
    }

    public function testDoNotClear(): void
    {
        new Meta('FakeVendor\HelloWorld', 'test-app');
        $this->assertFileExists($this->normalizePath(__DIR__ . '/Fake/fake-app/var/tmp/test-app/not-cleared.txt'));
    }

    public function testCustomTmpAndLogDir(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bear-app-meta-' . uniqid();
        $tmpDir = $base . DIRECTORY_SEPARATOR . 'tmp';
        $logDir = $base . DIRECTORY_SEPARATOR . 'log';
        $meta = new Meta('FakeVendor\HelloWorld', 'prod-app', '', $tmpDir, $logDir);
        $this->assertSame($this->normalizePath($tmpDir), $this->normalizePath($meta->tmpDir));
        $this->assertSame($this->normalizePath($logDir), $this->normalizePath($meta->logDir));
        $this->assertDirectoryExists($meta->tmpDir);
        $this->assertDirectoryExists($meta->logDir);
    }

    public function testCreateWritesUnderTheGivenBase(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bear-write-dir-' . uniqid();
        $meta = Meta::create('FakeVendor\\HelloWorld', 'prod-app', Meta::appDir('FakeVendor\\HelloWorld'), $base);
        $this->assertSame($this->normalizePath($base . '/FakeVendor/HelloWorld/prod-app/tmp'), $this->normalizePath($meta->tmpDir));
        $this->assertSame($this->normalizePath($base . '/FakeVendor/HelloWorld/prod-app/log'), $this->normalizePath($meta->logDir));
        $this->assertDirectoryExists($meta->tmpDir);
        $this->assertDirectoryExists($meta->logDir);
    }

    public function testCreateCarriesTheBaseItWasGiven(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bear-write-dir-' . uniqid();
        $appDir = Meta::appDir('FakeVendor\\HelloWorld');
        $this->assertSame($base, Meta::create('FakeVendor\\HelloWorld', 'prod-app', $appDir, $base)->writeDir);
        $this->assertNull(Meta::create('FakeVendor\\HelloWorld', 'prod-app', $appDir, null)->writeDir);
    }

    public function testCreateWithoutBaseWritesInItsOwnVar(): void
    {
        $meta = Meta::create('FakeVendor\\HelloWorld', 'prod-app', Meta::appDir('FakeVendor\\HelloWorld'), null);
        $this->assertSame($this->normalizePath($meta->appDir . '/var/tmp/prod-app'), $this->normalizePath($meta->tmpDir));
    }

    public function testAppDirResolvesFromTheAppModule(): void
    {
        $this->assertSame($this->meta->appDir, Meta::appDir('FakeVendor\\HelloWorld'));
    }

    public function testAppDirRefusesAnAppItCannotLocate(): void
    {
        $this->expectException(AppNameException::class);
        Meta::appDir('No\\Such\\App');
    }

    private function normalizePath(string $path): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }
}
