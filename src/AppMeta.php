<?php
/**
 * This file is part of the BEAR.AppMeta
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace BEAR\AppMeta;

use BEAR\AppMeta\Exception\AppNameException;
use BEAR\AppMeta\Exception\NotWritableException;
use Koriym\Psr4List\Psr4List;

class AppMeta extends AbstractAppMeta
{
    /**
     * Application meta
     *
     * @param string $name Application name{$Vendor\$Package}
     * @param string $contexts
     */
    public function __construct($name, $contexts = '')
    {
        $appModule = $name . '\Module\AppModule';
        if (! class_exists($appModule)) {
            throw new AppNameException($name);
        }
        $this->name = $name;
        $this->appDir = dirname(dirname(dirname((new \ReflectionClass($appModule))->getFileName())));
        $this->tmpDir = $this->appDir . '/var/tmp/'. $contexts;
        if (! file_exists($this->tmpDir)) {
            mkdir($this->tmpDir);
        }
        $this->logDir = $this->appDir . '/var/log';
        $isNotProd = strpos($contexts, 'prod') === false;
        if ($isNotProd) {
            $this->initForDevelop($this->tmpDir);
        }
    }

    /**
     * @return \Generator
     */
    public function getResourceListGenerator()
    {
        $list = new Psr4List;
        $resourceListGenerator = $list($this->name . '\Resource', $this->appDir . '/src/Resource');

        return $resourceListGenerator;
    }

    /**
     * @param string $dir
     */
    private function initForDevelop($dir)
    {
        $unlink = function ($path) use (&$unlink) {
            foreach (glob(rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*') as $file) {
                is_dir($file) ? $unlink($file) : unlink($file);
                @rmdir($file);
            }
        };
        $unlink($dir);
        if (! is_writable($this->tmpDir)) {
            throw new NotWritableException($this->tmpDir);
        }
        if (! is_writable($this->logDir)) {
            throw new NotWritableException($this->logDir);
        }
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache('user');
        }
    }
}
