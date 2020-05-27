<?php

declare(strict_types=1);

namespace BEAR\AppMeta;

use BEAR\AppMeta\Exception\AppNameException;
use BEAR\AppMeta\Exception\NotWritableException;
use PHPUnit\Framework\TestCase;

class MetaTest extends TestCase
{
    /**
     * @var Meta
     */
    protected $meta;

    protected function setUp() : void
    {
        parent::setUp();
        $app = dirname(__DIR__) . '/tests/Fake/fake-app/var/tmp';
        file_put_contents($app . '/app/cache', '1');
        chmod(__DIR__ . '/Fake/fake-not-writable/var', 0644);
        $this->meta = new Meta('FakeVendor\HelloWorld', 'prod-app');
    }

    protected function tearDown() : void
    {
        chmod(__DIR__ . '/Fake/fake-not-writable/var', 0777);
    }

    public function testNew() : void
    {
        $actual = $this->meta;
        $this->assertInstanceOf(Meta::class, $actual);
        $this->assertFileExists($this->meta->tmpDir);
    }

    public function testAppReflectorResourceList() : void
    {
        $Meta = new Meta('FakeVendor\HelloWorld');
        $classes = $files = [];
        foreach ($Meta->getResourceListGenerator() as list($class, $file)) {
            $classes[] = $class;
            $files[] = $file;
        }
        $expect = [
            'FakeVendor\HelloWorld\Resource\App\One',
            'FakeVendor\HelloWorld\Resource\App\Two',
            'FakeVendor\HelloWorld\Resource\App\User',
            'FakeVendor\HelloWorld\Resource\Page\Index',
            'FakeVendor\HelloWorld\Resource\App\Sub\Three',
            'FakeVendor\HelloWorld\Resource\App\Sub\Sub\Four'];
        $this->assertSame($expect, $classes);
        $expect = [
            $Meta->appDir . '/src/Resource/App/One.php',
            $Meta->appDir . '/src/Resource/App/Two.php',
            $Meta->appDir . '/src/Resource/App/User.php',
            $Meta->appDir . '/src/Resource/Page/Index.php',
            $Meta->appDir . '/src/Resource/App/Sub/Three.php',
            $Meta->appDir . '/src/Resource/App/Sub/Sub/Four.php'
        ];
        $this->assertSame($expect, $files);
    }

    public function testInvalidName() : void
    {
        $this->expectException(AppNameException::class);
        new Meta('Invalid\Invalid');
    }

    public function testNotWritable() : void
    {
        $this->expectException(NotWritableException::class);
        new Meta('FakeVendor\NotWritable');
    }

    public function testVarTmpFolderCreation() : void
    {
        new Meta('FakeVendor\HelloWorld', 'stage-app');
        $this->assertFileExists(__DIR__ . '/Fake/fake-app/var/log/stage-app');
        $this->assertFileExists(__DIR__ . '/Fake/fake-app/var/tmp/stage-app');
    }

    public function testDoNotClear() : void
    {
        new Meta('FakeVendor\HelloWorld', 'test-app');
        $this->assertFileExists(__DIR__ . '/Fake/fake-app/var/tmp/test-app/not-cleared.txt');
    }
}
