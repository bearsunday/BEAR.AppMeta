<?php

declare(strict_types=1);

namespace BEAR\AppMeta;

use BEAR\AppMeta\Exception\AppNameException;
use BEAR\AppMeta\Exception\NotWritableException;

/**
 * @deprecated Use Meta instead
 */
class AppMeta extends AbstractAppMeta
{
    /**
     * ClearDir flags not to delete multiple times in single request, Such as unit testing
     *
     * @var string[]
     */
    private static $cleanUpFlg = [];

    /**
     * @param string $name    application name      (Vendor\Project)
     * @param string $context application context   (prod-hal-app)
     * @param string $appDir  application directory
     */
    public function __construct(string $name, string $context = 'app', string $appDir = '')
    {
        $appModule = $name . '\Module\AppModule';
        if (! class_exists($appModule)) {
            throw new AppNameException($name);
        }
        $this->name = $name;
        $this->appDir = $appDir ?: dirname((new \ReflectionClass($appModule))->getFileName(), 3);
        $this->tmpDir = $this->appDir . '/var/tmp/' . $context;
        if (! file_exists($this->tmpDir) && ! @mkdir($this->tmpDir, 0777, true) && ! is_dir($this->tmpDir)) {
            throw new NotWritableException($this->tmpDir);
        }
        $this->logDir = $this->appDir . '/var/log/' . $context;
        if (! file_exists($this->logDir) && ! @mkdir($this->logDir, 0777, true) && ! is_dir($this->logDir)) {
            throw new NotWritableException($this->logDir);
        }
        $isClearable = strpos($context, 'prod-') === false
            && strpos($context, 'stage-') === false
            && ! in_array($this->tmpDir, self::$cleanUpFlg, true)
            && ! file_exists($this->tmpDir . '/.do_not_clear');
        if ($isClearable) {
            $this->clearTmpDirectory($this->tmpDir);
            self::$cleanUpFlg[] = $this->tmpDir;
        }
    }

    private function clearTmpDirectory(string $dir)
    {
        $unlink = function ($path) use (&$unlink) {
            foreach (glob(rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*') as $file) {
                is_dir($file) ? $unlink($file) : unlink($file);
                @rmdir($file);
            }
        };
        $unlink($dir);
    }
}
