<?php
/**
 * This file is part of the BEAR.AppMeta
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace BEAR\AppMeta;

abstract class AbstractAppMeta
{
    /**
     * Application name "{Vendor}\{Package}"
     *
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $appDir;

    /**
     * @var string
     */
    public $tmpDir;

    /**
     * @var string
     */
    public $logDir;

    /**
     * Return resource list generator
     *
     * @return \Generator
     */
    abstract public function getResourceListGenerator();
}
