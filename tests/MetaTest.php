<?php

declare(strict_types=1);

namespace BEAR\AppMeta;

use BEAR\AppMeta\Exception\AppDirNotAbsoluteException;
use BEAR\AppMeta\Exception\AppNameException;
use BEAR\AppMeta\Exception\WriteDirNotAbsoluteException;
use FakeVendor\HelloWorld\Resource\App\One;
use FakeVendor\HelloWorld\Resource\App\Sub\Sub\Four;
use FakeVendor\HelloWorld\Resource\App\Sub\Three;
use FakeVendor\HelloWorld\Resource\App\Two;
use FakeVendor\HelloWorld\Resource\App\User;
use FakeVendor\HelloWorld\Resource\Page\Index;
use PHPUnit\Framework\TestCase;

use function assert;
use function chmod;
use function dirname;
use function file_put_contents;
use function mkdir;
use function serialize;
use function sort;
use function sprintf;
use function str_replace;
use function strlen;
use function sys_get_temp_dir;
use function uniqid;
use function unserialize;

use const DIRECTORY_SEPARATOR;
use const PHP_OS_FAMILY;

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

    public function testConstructionCreatesNothing(): void
    {
        $appDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bear-no-var-' . uniqid();
        mkdir($appDir);
        $meta = new Meta('FakeVendor\HelloWorld', 'prod-app', $appDir);
        $this->assertDirectoryDoesNotExist($meta->tmpDir);
        $this->assertDirectoryDoesNotExist($meta->logDir);
        $this->assertDirectoryDoesNotExist($meta->buildDir);
        $this->assertDirectoryDoesNotExist($appDir . '/var');
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
        $this->assertSame($tmpDir, $meta->tmpDir);
        $this->assertSame($logDir, $meta->logDir);
    }

    public function testCreateWritesUnderTheGivenBase(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bear-write-dir-' . uniqid();
        $meta = Meta::create('FakeVendor\\HelloWorld', 'prod-app', Meta::appDir('FakeVendor\\HelloWorld'), $base);
        $this->assertSame($this->normalizePath($base . '/FakeVendor/HelloWorld/prod-app/tmp'), $this->normalizePath($meta->tmpDir));
        $this->assertSame($this->normalizePath($base . '/FakeVendor/HelloWorld/prod-app/log'), $this->normalizePath($meta->logDir));
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

    public function testBuildDirIsUnderTheApplication(): void
    {
        $meta = new Meta('FakeVendor\\HelloWorld', 'prod-app');
        $this->assertSame($this->normalizePath($meta->appDir . '/var/build/prod-app'), $this->normalizePath($meta->buildDir));
    }

    public function testBuildDirStaysUnderTheApplicationWhenTmpAndLogFollowTheBase(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bear-write-dir-' . uniqid();
        $appDir = Meta::appDir('FakeVendor\\HelloWorld');
        $meta = Meta::create('FakeVendor\\HelloWorld', 'prod-app', $appDir, $base);
        $this->assertSame($this->normalizePath($base . '/FakeVendor/HelloWorld/prod-app/tmp'), $this->normalizePath($meta->tmpDir));
        $this->assertSame($this->normalizePath($appDir . '/var/build/prod-app'), $this->normalizePath($meta->buildDir));
    }

    public function testBuildDirSeparatesContexts(): void
    {
        $appDir = Meta::appDir('FakeVendor\\HelloWorld');
        $this->assertSame($this->normalizePath($appDir . '/var/build/prod-app'), $this->normalizePath((new Meta('FakeVendor\\HelloWorld', 'prod-app'))->buildDir));
        $this->assertSame($this->normalizePath($appDir . '/var/build/stage-app'), $this->normalizePath((new Meta('FakeVendor\\HelloWorld', 'stage-app'))->buildDir));
    }

    public function testMetaIsBuiltForAnApplicationItCannotWriteTo(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('chmod does not write-protect a directory on Windows.');
        }

        $appDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bear-read-only-' . uniqid();
        mkdir($appDir . '/var', 0777, true);
        chmod($appDir . '/var', 0555);
        chmod($appDir, 0555);

        try {
            $meta = new Meta('FakeVendor\\HelloWorld', 'prod-app', $appDir);
            $this->assertSame($meta->appDir . '/var/tmp/prod-app', $meta->tmpDir);
            $this->assertDirectoryDoesNotExist($meta->tmpDir);
            $this->assertDirectoryDoesNotExist($meta->buildDir);
        } finally {
            chmod($appDir, 0777);
            chmod($appDir . '/var', 0777);
        }
    }

    /** @dataProvider baseThatTheCurrentDirectoryResolves */
    public function testCreateRefusesABaseThatIsNotAbsolute(string $base): void
    {
        $this->expectException(WriteDirNotAbsoluteException::class);
        Meta::create('FakeVendor\\HelloWorld', 'prod-app', Meta::appDir('FakeVendor\\HelloWorld'), $base);
    }

    /** @return array<string, array{0: string}> */
    public static function baseThatTheCurrentDirectoryResolves(): array
    {
        return ['empty' => [''], 'relative' => ['var/write'], 'dot' => ['./write']];
    }

    public function testUnserializeRelocatesPathsUnderTheAppDir(): void
    {
        $meta = new Meta('FakeVendor\\HelloWorld', 'prod-app');
        // spellings from a build machine the application has left since
        $meta->appDir = '/build/machine/app';
        $meta->tmpDir = '/build/machine/app/var/tmp/prod-app';
        $meta->logDir = '/build/machine/app/var/log/prod-app';
        $meta->buildDir = '/build/machine/app/var/build/prod-app';

        $woke = unserialize(serialize($meta));
        assert($woke instanceof Meta);
        $appDir = Meta::appDir('FakeVendor\\HelloWorld');
        $this->assertSame($this->normalizePath($appDir), $this->normalizePath($woke->appDir));
        $this->assertSame($this->normalizePath($appDir . '/var/tmp/prod-app'), $this->normalizePath($woke->tmpDir));
        $this->assertSame($this->normalizePath($appDir . '/var/log/prod-app'), $this->normalizePath($woke->logDir));
        $this->assertSame($this->normalizePath($appDir . '/var/build/prod-app'), $this->normalizePath($woke->buildDir));
    }

    public function testUnserializeAcceptsAPayloadWithoutBuildDir(): void
    {
        // what 1.12 baked: five fields, no buildDir
        $field = static fn (string $key, string $value): string => sprintf('s:%d:"%s";s:%d:"%s";', strlen($key), $key, strlen($value), $value);
        $payload = 'O:17:"BEAR\\AppMeta\\Meta":5:{'
            . $field('name', 'FakeVendor\\HelloWorld')
            . $field('appDir', '/build/machine/app')
            . $field('tmpDir', '/build/machine/app/var/tmp/prod-app')
            . $field('logDir', '/build/machine/app/var/log/prod-app')
            . 's:8:"writeDir";N;}';

        $woke = unserialize($payload);
        assert($woke instanceof Meta);
        $appDir = Meta::appDir('FakeVendor\\HelloWorld');
        $this->assertSame($this->normalizePath($appDir . '/var/tmp/prod-app'), $this->normalizePath($woke->tmpDir));
        $this->assertSame($this->normalizePath($appDir . '/var/log/prod-app'), $this->normalizePath($woke->logDir));
    }

    public function testWakeupKeepsPathsOutsideTheAppDir(): void
    {
        $meta = new Meta('FakeVendor\\HelloWorld', 'prod-app');
        $meta->appDir = '/build/machine/app';
        $meta->tmpDir = '/write/FakeVendor/HelloWorld/prod-app/tmp'; // writeDir-derived: move-invariant
        $meta->logDir = '/logs/FakeVendor/HelloWorld'; // a custom rule: move-invariant
        $meta->buildDir = '/build/machine/app/var/build/prod-app';

        $meta->__wakeup();
        $appDir = Meta::appDir('FakeVendor\\HelloWorld');
        $this->assertSame($this->normalizePath($appDir), $this->normalizePath($meta->appDir));
        $this->assertSame('/write/FakeVendor/HelloWorld/prod-app/tmp', $meta->tmpDir);
        $this->assertSame('/logs/FakeVendor/HelloWorld', $meta->logDir);
        $this->assertSame($this->normalizePath($appDir . '/var/build/prod-app'), $this->normalizePath($meta->buildDir));
    }

    public function testWakeupIsQuietWhenTheApplicationHasNotMoved(): void
    {
        $meta = new Meta('FakeVendor\\HelloWorld', 'prod-app');
        $before = [$meta->appDir, $meta->tmpDir, $meta->logDir, $meta->buildDir];
        $meta->__wakeup();
        $this->assertSame($before, [$meta->appDir, $meta->tmpDir, $meta->logDir, $meta->buildDir]);
    }

    public function testTmpAndLogDirSpellTheSameWhetherOrNotTheyExist(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bear-app-meta-' . uniqid();
        // a segment realpath() collapses, so a resolved spelling would differ once the directory exists
        $tmpDir = $base . '/./tmp';
        $logDir = $base . '/./log';
        $absent = new Meta('FakeVendor\\HelloWorld', 'prod-app', '', $tmpDir, $logDir);
        mkdir($base . '/tmp', 0777, true);
        mkdir($base . '/log', 0777, true);
        $present = new Meta('FakeVendor\\HelloWorld', 'prod-app', '', $tmpDir, $logDir);

        $this->assertSame($tmpDir, $absent->tmpDir);
        $this->assertSame($absent->tmpDir, $present->tmpDir);
        $this->assertSame($logDir, $absent->logDir);
        $this->assertSame($absent->logDir, $present->logDir);
    }

    public function testAnAppDirInsideAnArchiveIsCarriedAsGiven(): void
    {
        $meta = new Meta('FakeVendor\\HelloWorld', 'prod-app', 'phar:///nonexistent/app.phar');
        $this->assertSame('phar:///nonexistent/app.phar', $meta->appDir);
        $this->assertSame('phar:///nonexistent/app.phar/var/tmp/prod-app', $meta->tmpDir);
        $this->assertSame('phar:///nonexistent/app.phar/var/log/prod-app', $meta->logDir);
    }

    public function testRefusesAnAppDirThatIsNotAbsolute(): void
    {
        $this->expectException(AppDirNotAbsoluteException::class);
        new Meta('FakeVendor\\HelloWorld', 'prod-app', 'relative/app', sys_get_temp_dir() . '/bear-tmp-' . uniqid(), sys_get_temp_dir() . '/bear-log-' . uniqid());
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
