<?php
/**
 * This file is part of the BEAR.AppMeta package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace BEAR\AppMeta;

use BEAR\AppMeta\Exception\AppNameException;
use BEAR\AppMeta\Exception\NotWritableException;

class AppMeta extends AbstractAppMeta
{
    /**
     * @param string $name    application name    (Vendor\Project)
     * @param string $context application context (prod-hal-app)
     * @param string $appDir  application directory
     */
    public function __construct($name, $context = 'app', $appDir = null)
    {
        $appModule = $name . '\Module\AppModule';
        if (! class_exists($appModule)) {
            throw new AppNameException($name);
        }
        $this->name = $name;
        $this->appDir = $appDir ? $appDir : dirname(dirname(dirname((new \ReflectionClass($appModule))->getFileName())));
        $this->tmpDir = $this->appDir . '/var/tmp/' . $context;
        if (! file_exists($this->tmpDir) && ! mkdir($this->tmpDir, 0777, true) && ! is_dir($this->tmpDir) && ! is_writable($this->tmpDir)) {
            throw new NotWritableException($this->tmpDir);
        }
        $this->logDir = $this->appDir . '/var/log/' . $context;
        if (! file_exists($this->logDir) && ! mkdir($this->logDir, 0777, true) && ! is_dir($this->logDir) && ! is_writable($this->logDir)) {
            throw new NotWritableException($this->logDir);
        }
        $isCacheable = is_int(strpos($context, 'prod-')) || is_int(strpos($context, 'stage-'));
        if (! $isCacheable) {
            $this->clearTmpDirectory($this->tmpDir);
        }
    }

    /**
     * @param string $dir
     */
    private function clearTmpDirectory($dir)
    {
        /**
         * A flag for not deleting tmp directories many times with single request
         */
        static $cleanUpFlg = [];

        if (in_array($dir, $cleanUpFlg, true) || file_exists($dir . '/.do_not_clear')) {
            return;
        }
        $unlink = function ($path) use (&$unlink) {
            foreach (glob(rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*') as $file) {
                is_dir($file) ? $unlink($file) : unlink($file);
                @rmdir($file);
            }
        };
        $unlink($dir);
        $cleanUpFlg[] = $dir;
    }
}
