<?php

declare(strict_types=1);

namespace BEAR\AppMeta;

use BEAR\AppMeta\Exception\AppNameException;
use BEAR\AppMeta\Exception\NotWritableException;
use FakeVendor\HelloWorld\Resource\App\One;
use FakeVendor\HelloWorld\Resource\App\Sub\Sub\Four;
use FakeVendor\HelloWorld\Resource\App\Sub\Three;
use FakeVendor\HelloWorld\Resource\App\Two;
use FakeVendor\HelloWorld\Resource\App\User;
use FakeVendor\HelloWorld\Resource\Page\Index;
use PHPUnit\Framework\TestCase;

use function chmod;
use function dirname;
use function file_put_contents;

class MetaTest extends TestCase
{
    /** @var Meta */
    protected $meta;

    protected function setUp(): void
    {
        parent::setUp();
        $app = dirname(__DIR__) . '/tests/Fake/fake-app/var/tmp';
        file_put_contents($app . '/app/cache', '1');
        chmod(__DIR__ . '/Fake/fake-not-writable/var', 0644);
        $this->meta = new Meta('FakeVendor\HelloWorld', 'prod-app');
    }

    protected function tearDown(): void
    {
        chmod(__DIR__ . '/Fake/fake-not-writable/var', 0777);
    }

    public function testNew(): void
    {
        $actual = $this->meta;
        $this->assertInstanceOf(Meta::class, $actual);
        $this->assertFileExists($this->meta->tmpDir);
    }

    public function testAppReflectorResourceList(): void
    {
        $meta = new Meta('FakeVendor\HelloWorld');
        $classes = $files = [];
        foreach ($meta->getResourceListGenerator() as [$class, $file]) {
            $classes[] = $class;
            $files[] = $file;
        }

        $expect = [
            One::class,
            Two::class,
            User::class,
            Index::class,
            Three::class,
            Four::class,
        ];
        $this->assertSame($expect, $classes);
        $expect = [
            $meta->appDir . '/src/Resource/App/One.php',
            $meta->appDir . '/src/Resource/App/Two.php',
            $meta->appDir . '/src/Resource/App/User.php',
            $meta->appDir . '/src/Resource/Page/Index.php',
            $meta->appDir . '/src/Resource/App/Sub/Three.php',
            $meta->appDir . '/src/Resource/App/Sub/Sub/Four.php',
        ];
        $this->assertSame($expect, $files);
    }

    public function testInvalidName(): void
    {
        $this->expectException(AppNameException::class);
        new Meta('Invalid\Invalid');
    }

    public function testNotWritable(): void
    {
        $this->expectException(NotWritableException::class);
        new Meta('FakeVendor\NotWritable');
    }

    public function testVarTmpFolderCreation(): void
    {
        new Meta('FakeVendor\HelloWorld', 'stage-app');
        $this->assertFileExists(__DIR__ . '/Fake/fake-app/var/log/stage-app');
        $this->assertFileExists(__DIR__ . '/Fake/fake-app/var/tmp/stage-app');
    }

    public function testDoNotClear(): void
    {
        new Meta('FakeVendor\HelloWorld', 'test-app');
        $this->assertFileExists(__DIR__ . '/Fake/fake-app/var/tmp/test-app/not-cleared.txt');
    }
}
