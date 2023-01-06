<?php

declare(strict_types=1);

namespace BEAR\AppMeta;

use BEAR\Resource\ResourceObject;
use Generator;
use Koriym\Psr4List\Psr4List;

use function array_slice;
use function array_walk;
use function explode;
use function implode;
use function is_a;
use function ltrim;
use function preg_replace;
use function sprintf;
use function strtolower;

abstract class AbstractAppMeta
{
    /**
     * Application name "{Vendor}\{Project}"
     *
     * @var string
     */
    public $name;

    /** @var string */
    public $appDir;

    /** @var string */
    public $tmpDir;

    /** @var string */
    public $logDir;

    /** @return Generator<array{0: class-string<ResourceObject>, 1: string}> */
    public function getResourceListGenerator(): Generator
    {
        $list = new Psr4List();

        foreach ($list($this->name . '\Resource', $this->appDir . '/src/Resource') as [$class, $file]) {
            if (! is_a($class, ResourceObject::class, true)) {
                continue;
            }

            yield [$class, $file];
        }
    }

    /**
     * @param string $scheme 'app' | 'page' | '*'
     *
     * @return Generator<ResMeta>
     */
    public function getGenerator(string $scheme = '*'): Generator
    {
        foreach ($this->getResourceListGenerator() as [$class, $file]) {
            $paths = explode('\\', $class);
            /** @var array<string> $paths */
            $path = array_slice($paths, 3);
            array_walk($path, [$this, 'camel2kebab']);
            if ($scheme === '*') {
                /** @var array<string> $slice */
                $slice = array_slice($path, 1);
                $uri = sprintf('%s://self/%s', (string) $path[0], implode('/', $slice));

                yield new ResMeta($uri, $class, $file);
            }

            if ($scheme === $path[0]) {
                /** @var array<string> $sliceSchema */
                $sliceSchema = array_slice($path, 1);
                $uri = sprintf('/%s', implode('/', $sliceSchema));

                yield new ResMeta($uri, $class, $file);
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     *
     * False positive: used in array_walk
     */
    private function camel2kebab(string &$str): void  // phpcs:ignore
    {
        $str = ltrim(strtolower((string) preg_replace('/[A-Z]/', '-\0', $str)), '-');
    }
}
