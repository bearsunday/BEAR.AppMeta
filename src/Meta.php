<?php

declare(strict_types=1);

namespace BEAR\AppMeta;

use BEAR\AppMeta\Exception\WriteDirNotAbsoluteException;

use function preg_match;
use function realpath;
use function rtrim;
use function str_replace;

/**
 * @psalm-import-type AppName from Types
 * @psalm-import-type Context from Types
 * @psalm-import-type AppDir from Types
 * @psalm-import-type TmpDir from Types
 * @psalm-import-type LogDir from Types
 * @psalm-import-type WriteDir from Types
 */
final class Meta extends AbstractAppMeta
{
    /** A leading slash, a UNC share, a drive letter, or a stream scheme. */
    private const ABSOLUTE = '#^(/|\\\\\\\\|[A-Za-z]:[/\\\\]|[A-Za-z][A-Za-z0-9+.\-]*://)#';

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
     * Meta writing under {writeDir}/{Vendor}/{Project}/{context}, or its own var/ when null.
     *
     * A boot must pass what the compile passed: differ on any argument and they read different files.
     *
     * @param AppName     $name
     * @param Context     $context
     * @param AppDir      $appDir
     * @param string|null $writeDir absolute base, checked here; where it lies is the caller's business
     *
     * @throws WriteDirNotAbsoluteException
     */
    public static function create(string $name, string $context, string $appDir, string|null $writeDir): self
    {
        if ($writeDir === null) {
            return new self($name, $context, $appDir);
        }

        // A base the current directory resolves lands somewhere else on the next run
        if (! self::isAbsolute($writeDir)) {
            throw new WriteDirNotAbsoluteException($writeDir);
        }

        $base = rtrim($writeDir, '/\\') . '/' . str_replace('\\', '/', $name) . '/' . $context;
        $meta = new self($name, $context, $appDir, $base . '/tmp', $base . '/log');
        /** @psalm-suppress DeprecatedProperty the factory still fills what releases read */
        $meta->writeDir = $writeDir;

        return $meta;
    }

    /**
     * @psalm-assert-if-true non-empty-string $dir
     * @phpstan-assert-if-true non-empty-string $dir
     */
    private static function isAbsolute(string $dir): bool
    {
        return (bool) preg_match(self::ABSOLUTE, $dir);
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
