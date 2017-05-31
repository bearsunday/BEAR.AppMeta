<?php

namespace BEAR\AppMeta;

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
        $this->appMeta = new AppMeta('FakeVendor\HelloWorld', 'prod-app');
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
            'FakeVendor\HelloWorld\Resource\App\Sub\Sub\Four'        ];
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

    /**
     * @expectedException \BEAR\AppMeta\Exception\AppNameException
     */
    public function testInvalidName()
    {
        new AppMeta('Invalid\Invalid');
    }

    public function testDev()
    {
        new AppMeta('FakeVendor\HelloWorld', 'app');
        new AppMeta('FakeVendor\HelloWorld', 'app-' . uniqid());
    }
}
