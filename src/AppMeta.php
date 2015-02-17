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
     * Application name
     *
     * {$Vendor\$Package}
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $appModule = $name . '\Module\AppModule';
        if (! class_exists($appModule)) {
            throw new AppNameException($name);
        }
        $this->name = $name;
        $this->appDir = dirname(dirname(dirname((new \ReflectionClass($appModule))->getFileName())));
        $this->tmpDir = $this->appDir . '/var/tmp';
        if (! is_writable($this->tmpDir)) {
            throw new NotWritableException($this->tmpDir);
        }
        $this->logDir = $this->appDir . '/var/log';
        if (! is_writable($this->logDir)) {
            throw new NotWritableException($this->logDir);
        }
    }

    /**
     * @return \Generator
     */
    public function getResourceListGenerator()
    {
        $list = new Psr4List;
        $resourceListGenerator =  $list($this->name . '\Resource', $this->appDir . '/src/Resource');

        return $resourceListGenerator;
    }
}
