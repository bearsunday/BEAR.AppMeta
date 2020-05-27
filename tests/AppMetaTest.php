<?php

declare(strict_types=1);

namespace BEAR\AppMeta;

use BEAR\AppMeta\Exception\AppNameException;
use BEAR\AppMeta\Exception\NotWritableException;
use FakeVendor\HelloWorld\Resource\App\One;
use PHPUnit\Framework\TestCase;

class AppMetaTest extends TestCase
{
    /**
     * @var Meta
     */
    protected $appMeta;

    protected function setUp() : void
    {
        parent::setUp();
        $app = dirname(__DIR__) . '/tests/Fake/fake-app/var/tmp';
        file_put_contents($app . '/app/cache', '1');
        chmod(__DIR__ . '/Fake/fake-not-writable/var', 0644);
        $this->appMeta = new Meta('FakeVendor\HelloWorld', 'prod-app');
    }

    protected function tearDown() : void
    {
        chmod(__DIR__ . '/Fake/fake-not-writable/var', 0777);
    }

    public function testNew() : void
    {
        $actual = $this->appMeta;
        $this->assertInstanceOf('\BEAR\AppMeta\Meta', $actual);
        $this->assertFileExists($this->appMeta->tmpDir);
    }

    public function testAppReflectorResourceList() : void
    {
        $appMeta = new Meta('FakeVendor\HelloWorld');
        $classes = $files = [];
        foreach ($appMeta->getResourceListGenerator() as list($class, $file)) {
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
            $appMeta->appDir . '/src/Resource/App/One.php',
            $appMeta->appDir . '/src/Resource/App/Two.php',
            $appMeta->appDir . '/src/Resource/App/User.php',
            $appMeta->appDir . '/src/Resource/Page/Index.php',
            $appMeta->appDir . '/src/Resource/App/Sub/Three.php',
            $appMeta->appDir . '/src/Resource/App/Sub/Sub/Four.php'
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

    public function testGetGeneratorApp() : void
    {
        $appMeta = new Meta('FakeVendor\HelloWorld');
        $uris = [];
        foreach ($appMeta->getGenerator('app') as $uri) {
            $uris[] = $uri;
        }
        $this->assertCount(5, $uris);
        $this->assertSame('/one', $uris[0]->uriPath);
        $this->assertSame(One::class, $uris[0]->class);
        $this->assertContains('tests/Fake/fake-app/src/Resource/App/One.php', $uris[0]->filePath);
    }

    public function testGetGeneratorAll() : void
    {
        $appMeta = new Meta('FakeVendor\HelloWorld');
        $uris = [];
        foreach ($appMeta->getGenerator('*') as $uri) {
            $uris[] = $uri;
        }
        $this->assertCount(6, $uris);
        $this->assertSame('app://self/one', $uris[0]->uriPath);
        $this->assertSame(One::class, $uris[0]->class);
        $this->assertContains('tests/Fake/fake-app/src/Resource/App/One.php', $uris[0]->filePath);
    }
}
