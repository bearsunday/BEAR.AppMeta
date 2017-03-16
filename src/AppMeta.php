<?php
/**
 * This file is part of the BEAR.Sunday package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace BEAR\AppMeta;

use BEAR\AppMeta\Exception\AppNameException;
use BEAR\AppMeta\Exception\NotWritableException;
use Koriym\Psr4List\Psr4List;

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
        if (! file_exists($this->tmpDir) && mkdir($this->tmpDir) && ! is_writable($this->tmpDir)) {
            throw new NotWritableException($this->tmpDir);
        }
        $this->logDir = $this->appDir . '/var/log';
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
}
