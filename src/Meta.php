<?php

declare(strict_types=1);

namespace BEAR\AppMeta;

use function realpath;

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
        $appDir = $appDir !== '' ? $appDir : self::appDir($name);

        $this->appDir = self::normalize($appDir);
        $this->buildDir = $this->appDir . '/var/build/' . $context;
        $this->tmpDir = $tmpDir ?? $this->appDir . '/var/tmp/' . $context;
        $this->logDir = $logDir ?? $this->appDir . '/var/log/' . $context;
    }

    /**
     * @param non-empty-string $dir
     *
     * @return non-empty-string
     */
    private static function normalize(string $dir): string
    {
        $real = realpath($dir);
        if ($real === false) {
            return $dir;
        }

        /** @var non-empty-string $real */
        return $real;
    }
}
