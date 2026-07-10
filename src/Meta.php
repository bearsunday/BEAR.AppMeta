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
use function rtrim;
use function sprintf;

/**
 * @psalm-import-type AppName from Types
 * @psalm-import-type Context from Types
 * @psalm-import-type AppDir from Types
 * @psalm-import-type TmpDir from Types
 * @psalm-import-type LogDir from Types
 */
final class Meta extends AbstractAppMeta
{
    /**
     * @param AppName     $name    application name      (Vendor\Project)
     * @param Context     $context application context   (prod-hal-app)
     * @param string      $appDir  application directory
     * @param TmpDir|null $tmpDir  writable tmp directory (default: {appDir}/var/tmp/{context})
     * @param LogDir|null $logDir  log directory (default: {appDir}/var/log/{context})
     */
    public function __construct(
        string $name,
        string $context = 'app',
        string $appDir = '',
        string|null $tmpDir = null,
        string|null $logDir = null,
    ) {
        $this->name = $name;
        $this->appDir = $appDir !== '' ? $appDir : $this->getAppDir($name);
        $this->tmpDir = $this->ensureDir($tmpDir ?? $this->appDir . '/var/tmp/' . $context);
        $this->logDir = $this->ensureDir($logDir ?? $this->appDir . '/var/log/' . $context);
    }

    /**
     * @param non-empty-string $dir
     *
     * @return non-empty-string
     */
    private function ensureDir(string $dir): string
    {
        $dir = rtrim($dir, '/\\');
        assert($dir !== '');
        if (! file_exists($dir) && ! @mkdir($dir, 0777, true) && ! is_dir($dir)) {
            throw new NotWritableException($dir);
        }

        return $dir;
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
