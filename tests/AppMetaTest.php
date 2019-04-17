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
     * @var AppMeta
     */
    protected $appMeta;

    protected function setUp()
    {
        parent::setUp();
        $app = dirname(__DIR__) . '/tests/Fake/fake-app/var/tmp';
        file_put_contents($app . '/app/cache', '1');
        chmod(__DIR__ . '/Fake/fake-not-writable/var', 0644);
        $this->appMeta = new AppMeta('FakeVendor\HelloWorld', 'prod-app');
    }

    protected function tearDown()
    {
        chmod(__DIR__ . '/Fake/fake-not-writable/var', 0777);
    }

    public function testNew()
    {
        $actual = $this->appMeta;
        $this->assertInstanceOf('\BEAR\AppMeta\AppMeta', $actual);
        $this->assertFileExists($this->appMeta->tmpDir);
    }

    public function testAppReflectorResourceList()
    {
        $appMeta = new AppMeta('FakeVendor\HelloWorld');
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

    public function testInvalidName()
    {
        $this->expectException(AppNameException::class);
        new AppMeta('Invalid\Invalid');
    }

    public function testNotWritable()
    {
        $this->expectException(NotWritableException::class);
        new AppMeta('FakeVendor\NotWritable');
    }

    public function testVarTmpFolderCreation()
    {
        new AppMeta('FakeVendor\HelloWorld', 'stage-app');
        $this->assertFileExists(__DIR__ . '/Fake/fake-app/var/log/stage-app');
        $this->assertFileExists(__DIR__ . '/Fake/fake-app/var/tmp/stage-app');
    }

    public function testDoNotClear()
    {
        new AppMeta('FakeVendor\HelloWorld', 'test-app');
        $this->assertFileExists(__DIR__ . '/Fake/fake-app/var/tmp/test-app/not-cleared.txt');
    }

    public function testUriList()
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
}
