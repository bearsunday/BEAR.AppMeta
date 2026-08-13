<?php

declare(strict_types=1);

namespace BEAR\AppMeta;

use BEAR\Resource\ResourceObject;
use Generator;
use Koriym\Psr4List\Psr4List;

use function array_slice;
use function array_walk;
use function assert;
use function explode;
use function implode;
use function is_a;
use function ltrim;
use function preg_replace;
use function sprintf;
use function strtolower;

use const DIRECTORY_SEPARATOR;

/**
 * @psalm-import-type AppName from Types
 * @psalm-import-type AppDir from Types
 * @psalm-import-type TmpDir from Types
 * @psalm-import-type ScriptDir from Types
 * @psalm-import-type LogDir from Types
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

    /** @var TmpDir */
    public string $tmpDir;

    /** @var ScriptDir */
    public string $scriptDir;

    /** @var LogDir */
    public string $logDir;

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
