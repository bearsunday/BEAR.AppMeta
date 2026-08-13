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
use function str_ends_with;
use function str_replace;

/**
 * @psalm-import-type AppName from Types
 * @psalm-import-type Context from Types
 * @psalm-import-type AppDir from Types
 * @psalm-import-type TmpDir from Types
 * @psalm-import-type LogDir from Types
 * @psalm-import-type ScriptDir from Types
 */
final class Meta extends AbstractAppMeta
{
    /**
     * @param AppName        $name      application name      (Vendor\Project)
     * @param Context        $context   application context   (prod-hal-app)
     * @param string         $appDir    application directory
     * @param TmpDir|null    $tmpDir    writable base for scratch (default: {appDir}/var/tmp/{context})
     * @param LogDir|null    $logDir    log base (default: {appDir}/var/log/{context})
     * @param ScriptDir|null $scriptDir compiled DI script base (default: {appDir}/var/tmp/{context}/di)
     */
    public function __construct(
        string $name,
        string $context = 'app',
        string $appDir = '',
        string|null $tmpDir = null,
        string|null $logDir = null,
        string|null $scriptDir = null,
    ) {
        $this->name = $name;
        $this->appDir = $appDir !== '' ? $appDir : $this->getAppDir($name);
        $this->tmpDir = $this->ensureDir($this->resolve($tmpDir, $this->appDir . '/var/tmp/' . $context, $context));
        $this->logDir = $this->ensureDir($this->resolve($logDir, $this->appDir . '/var/log/' . $context, $context));
        $this->scriptDir = $this->ensureDir($this->resolve($scriptDir, $this->appDir . '/var/tmp/' . $context . '/di', $context));
    }

    /**
     * Layer a given base by app and context, so one directory can serve several. Idempotent.
     *
     * @param non-empty-string $default
     * @param Context          $context
     *
     * @return non-empty-string
     */
    private function resolve(string|null $base, string $default, string $context): string
    {
        if ($base === null) {
            return $default;
        }

        $base = rtrim($base, '/\\');
        $suffix = '/' . str_replace('\\', '/', $this->name) . '/' . $context;

        return str_ends_with($base, $suffix) ? $base : $base . $suffix;
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
