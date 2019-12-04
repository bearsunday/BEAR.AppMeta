<?php

declare(strict_types=1);

namespace BEAR\AppMeta;

use BEAR\AppMeta\Exception\AppNameException;
use BEAR\AppMeta\Exception\NotWritableException;

class Meta extends AbstractAppMeta
{
    /**
     * @param string $name    application name      (Vendor\Project)
     * @param string $context application context   (prod-hal-app)
     * @param string $appDir  application directory
     */
    public function __construct(string $name, string $context = 'app', string $appDir = '')
    {
        $this->name = $name;
        $this->appDir = $appDir ?: $this->getAppDir($name);
        $this->tmpDir = $this->appDir . '/var/tmp/' . $context;
        if (! file_exists($this->tmpDir) && ! @mkdir($this->tmpDir, 0777, true) && ! is_dir($this->tmpDir)) {
            throw new NotWritableException($this->tmpDir);
        }
        $this->logDir = $this->appDir . '/var/log/' . $context;
        if (! file_exists($this->logDir) && ! @mkdir($this->logDir, 0777, true) && ! is_dir($this->logDir)) {
            throw new NotWritableException($this->logDir);
        }
    }

    private function getAppDir(string $name) : string
    {
        $module = $name . '\Module\AppModule';
        if (! class_exists($module)) {
            throw new AppNameException($name);
        }

        return dirname((string) (new \ReflectionClass($module))->getFileName(), 3);
    }
}
