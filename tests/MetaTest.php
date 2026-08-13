<?php

declare(strict_types=1);

namespace BEAR\AppMeta;

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

    /**
     * A given base is shared: a deploy hands one directory and knows neither app nor context, so
     * both are layered under it - two apps or contexts in one directory would silently answer with
     * each other's DI scripts and cache entries.
     */
    public function testCustomTmpAndLogDirAreLayeredByAppAndContext(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bear-app-meta-' . uniqid();
        $tmpDir = $base . DIRECTORY_SEPARATOR . 'tmp';
        $logDir = $base . DIRECTORY_SEPARATOR . 'log';
        $meta = new Meta('FakeVendor\HelloWorld', 'prod-app', '', $tmpDir, $logDir);
        $this->assertSame($this->normalizePath($tmpDir . '/FakeVendor/HelloWorld/prod-app'), $this->normalizePath($meta->tmpDir));
        $this->assertSame($this->normalizePath($logDir . '/FakeVendor/HelloWorld/prod-app'), $this->normalizePath($meta->logDir));
        $this->assertDirectoryExists($meta->tmpDir);
        $this->assertDirectoryExists($meta->logDir);
    }

    /** The namespace separator maps to a directory separator, so distinct apps never share a directory. */
    public function testLayeredPathKeepsUnderscoresDistinctFromNamespaces(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bear-app-meta-' . uniqid();
        $appDir = __DIR__ . '/Fake/fake-app';
        $underscore = new Meta('My_Vendor\App', 'prod-app', $appDir, $base);
        $namespaced = new Meta('My\Vendor_App', 'prod-app', $appDir, $base);
        $this->assertNotSame($namespaced->tmpDir, $underscore->tmpDir);
        $this->assertSame($this->normalizePath($base . '/My_Vendor/App/prod-app'), $this->normalizePath($underscore->tmpDir));
        $this->assertSame($this->normalizePath($base . '/My/Vendor_App/prod-app'), $this->normalizePath($namespaced->tmpDir));
    }

    public function testScriptDirDefaultsUnderTmpDirOfTheAppDir(): void
    {
        $meta = new Meta('FakeVendor\HelloWorld', 'prod-app');
        $this->assertSame($this->normalizePath($meta->appDir . '/var/tmp/prod-app/di'), $this->normalizePath($meta->scriptDir));
        $this->assertDirectoryExists($meta->scriptDir);
    }

    /**
     * Compiled scripts are a build output: they ship in the deployment artifact and are read at
     * runtime, where the artifact may be read-only. Moving tmpDir to a writable volume must not
     * drag them along, or every cold start recompiles what the build already produced.
     */
    public function testScriptDirDoesNotFollowATmpDirOverride(): void
    {
        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bear-app-meta-' . uniqid();
        $meta = new Meta('FakeVendor\HelloWorld', 'prod-app', '', $tmpDir);
        $this->assertSame($this->normalizePath($meta->appDir . '/var/tmp/prod-app/di'), $this->normalizePath($meta->scriptDir));
        $this->assertStringStartsWith($this->normalizePath($tmpDir), $this->normalizePath($meta->tmpDir));
    }

    /** A compile delegated to a child process rebuilds Meta from the resolved dirs; layering twice would split them. */
    public function testLayeringIsIdempotent(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bear-app-meta-' . uniqid();
        $first = new Meta('FakeVendor\HelloWorld', 'prod-app', '', $base);
        $rebuilt = new Meta('FakeVendor\HelloWorld', 'prod-app', '', $first->tmpDir);
        $this->assertSame($first->tmpDir, $rebuilt->tmpDir);
    }

    public function testScriptDirOverrideIsLayeredToo(): void
    {
        $scriptDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bear-app-meta-' . uniqid();
        $meta = new Meta('FakeVendor\HelloWorld', 'prod-app', '', null, null, $scriptDir);
        $this->assertSame($this->normalizePath($scriptDir . '/FakeVendor/HelloWorld/prod-app'), $this->normalizePath($meta->scriptDir));
    }

    private function normalizePath(string $path): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }
}
