<?php

declare(strict_types=1);

namespace BEAR\AppMeta;

use BEAR\AppMeta\Exception\AppNameException;
use BEAR\Resource\ResourceObject;
use Generator;
use Koriym\Psr4List\Psr4List;
use ReflectionClass;

use function array_slice;
use function array_walk;
use function assert;
use function class_exists;
use function dirname;
use function explode;
use function implode;
use function is_a;
use function ltrim;
use function preg_replace;
use function realpath;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;

use const DIRECTORY_SEPARATOR;

/**
 * @psalm-import-type AppName from Types
 * @psalm-import-type AppDir from Types
 * @psalm-import-type TmpDir from Types
 * @psalm-import-type LogDir from Types
 * @psalm-import-type BuildDir from Types
 * @psalm-import-type UriPath from Types
 * @psalm-import-type FilePath from Types
 * @psalm-import-type Scheme from Types
 */
abstract class AbstractAppMeta
{
    /**
     * Application name "{Vendor}\{Project}"
     *
     * @var AppName
     */
    public string $name;

    /** @var AppDir */
    public string $appDir;

    /**
     * Data derived at runtime
     *
     * @var TmpDir
     */
    public string $tmpDir;

    /** @var LogDir */
    public string $logDir;

    /**
     * Artifacts derived from the source
     *
     * @var BuildDir
     */
    public string $buildDir;

    /**
     * Re-point the paths under the application directory when it has moved or changed spelling.
     *
     * A serialized Meta was made on the build machine. Only paths below appDir go stale
     * when the tree or archive moves; a directory named outside it is the declaration's,
     * and left untouched.
     */
    public function __wakeup(): void
    {
        $from = str_replace('\\', '/', $this->appDir);
        /** @var AppDir $to rebased paths spell forward-slashed, whatever the platform */
        $to = str_replace('\\', '/', self::appDir($this->name));
        if ($from === $to) {
            return;
        }

        foreach (['tmpDir', 'logDir', 'buildDir'] as $dir) {
            /** @var string|null $path null only for 1.12 payloads, which carry no buildDir */
            $path = $this->$dir ?? null;
            if ($path === null) {
                continue;
            }

            $path = str_replace('\\', '/', $path);
            if (str_starts_with($path, $from . '/')) {
                $this->$dir = $to . substr($path, strlen($from));
            }
        }

        $this->appDir = $to;
    }

    /**
     * The directory of an application, resolved from its AppModule.
     *
     * @param AppName $name
     *
     * @return AppDir the canonical spelling
     *
     * @throws AppNameException
     */
    public static function appDir(string $name): string
    {
        $module = $name . '\Module\AppModule';
        if (! class_exists($module)) {
            throw new AppNameException($name);
        }

        $fileName = (new ReflectionClass($module))->getFileName();
        assert($fileName !== false, sprintf('Cannot locate AppModule file: %s', $module));

        $dir = dirname($fileName, 3);
        $real = realpath($dir);

        /** @var AppDir $canonical */
        $canonical = $real === false ? $dir : $real;

        return $canonical;
    }

    /** @return Generator<array{0: class-string<ResourceObject>, 1: FilePath}> */
    public function getResourceListGenerator(): Generator
    {
        $list = new Psr4List();
        $resourceNamespace = $this->name . '\Resource';
        $resourceDir = $this->appDir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Resource';

        foreach ($list($resourceNamespace, $resourceDir) as [$class, $file]) {
            if (! is_a($class, ResourceObject::class, true)) {
                continue;
            }

            assert($file !== '');

            yield [$class, $file];
        }
    }

    /**
     * @param Scheme $scheme 'app' | 'page' | '*'
     *
     * @return Generator<ResMeta>
     */
    public function getGenerator(string $scheme = '*'): Generator
    {
        foreach ($this->getResourceListGenerator() as [$class, $file]) {
            /** @var array<non-empty-string> $paths */
            $paths = explode('\\', $class);
            $path = array_slice($paths, 3);
            array_walk($path, [$this, 'camel2kebab']);
            if ($scheme === '*') {
                /** @var non-empty-string $schemeValue */
                $schemeValue = $path[0];
                /** @var array<non-empty-string> $slice */
                $slice = array_slice($path, 1);
                $uri = sprintf('%s://self/%s', $schemeValue, implode('/', $slice));

                yield new ResMeta($uri, $class, $file);
            }

            if ($scheme === $path[0]) {
                /** @var array<non-empty-string> $sliceSchema */
                $sliceSchema = array_slice($path, 1);
                $uri = sprintf('/%s', implode('/', $sliceSchema));

                yield new ResMeta($uri, $class, $file);
            }
        }
    }

    /**
     * False positive: used in array_walk
     *
     * @param non-empty-string $str
     *
     * @param-out non-empty-string $str
     */
    private function camel2kebab(string &$str): void  // phpcs:ignore
    {
        $result = ltrim(strtolower((string) preg_replace('/[A-Z]/', '-\0', $str)), '-');
        assert($result !== '');
        $str = $result;
    }
}
