<?php

declare(strict_types=1);

namespace BEAR\AppMeta;

use Koriym\Psr4List\Psr4List;

abstract class AbstractAppMeta
{
    /**
     * Application name "{Vendor}\{Project}"
     *
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $appDir;

    /**
     * @var string
     */
    public $tmpDir;

    /**
     * @var string
     */
    public $logDir;

    /**
     * @return \Generator<array{0: string, 1: string}>
     */
    public function getResourceListGenerator() : \Generator
    {
        $list = new Psr4List;

        return $list($this->name . '\Resource', $this->appDir . '/src/Resource');
    }

    /**
     * @param string $scheme 'app' | 'page' | '*'
     *
     * @return \Generator<ResMeta>
     */
    public function getGenerator(string $scheme = '*') : \Generator
    {
        foreach ($this->getResourceListGenerator() as list($class, $file)) {
            $paths = explode('\\', $class);
            /** @var array<string> $paths */
            $path = array_slice($paths, 3);
            array_walk($path, [$this, 'camel2kebab']);
            if ($scheme === '*') {
                $uri = sprintf('%s://self/%s', (string) $path[0], implode('/', array_slice($path, 1)));

                yield new ResMeta($uri, $class, $file);
            }
            if ($scheme === $path[0]) {
                $uri = sprintf('/%s', implode('/', array_slice($path, 1)));

                yield new ResMeta($uri, $class, $file);
            }
        }
    }

    private function camel2kebab(string &$str) : void
    {
        $str = ltrim(strtolower((string) preg_replace('/[A-Z]/', '-\0', $str)), '-');
    }
}
