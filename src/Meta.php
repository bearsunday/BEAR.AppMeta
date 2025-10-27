<?php

declare(strict_types=1);

namespace BEAR\AppMeta;

use BEAR\AppMeta\Exception\AppNameException;
use BEAR\AppMeta\Exception\NotWritableException;
use ReflectionClass;

use function assert;
use function class_exists;
use function dirname;
use function file_exists;
use function is_dir;
use function mkdir;
use function sprintf;

use const DIRECTORY_SEPARATOR;

/**
 * @psalm-import-type AppName from Types
 * @psalm-import-type Context from Types
 * @psalm-import-type AppDir from Types
 */
final class Meta extends AbstractAppMeta
{
    /**
     * @param AppName $name    application name      (Vendor\Project)
     * @param Context $context application context   (prod-hal-app)
     * @param string  $appDir  application directory
     */
    public function __construct(string $name, string $context = 'app', string $appDir = '')
    {
        $this->name = $name;
        $this->appDir = $appDir ?: $this->getAppDir($name);
        $this->tmpDir = $this->appDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . $context;
        if (! file_exists($this->tmpDir) && ! @mkdir($this->tmpDir, 0777, true) && ! is_dir($this->tmpDir)) {
            throw new NotWritableException($this->tmpDir);
        }

        $this->logDir = $this->appDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . $context;
        if (! file_exists($this->logDir) && ! @mkdir($this->logDir, 0777, true) && ! is_dir($this->logDir)) {
            throw new NotWritableException($this->logDir); // @codeCoverageIgnore
        }
    }

    /**
     * @param AppName $name
     *
     * @return AppDir
     */
    private function getAppDir(string $name): string
    {
        $module = $name . '\Module\AppModule';
        if (! class_exists($module)) {
            throw new AppNameException($name);
        }

        $fileName = (new ReflectionClass($module))->getFileName();
        assert($fileName !== false, sprintf('Cannot locate AppModule file: %s', $module));

        /** @var AppDir $dir */
        $dir = dirname($fileName, 3);
        assert($dir !== '.');

        return $dir;
    }
}
