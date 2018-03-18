<?php

declare(strict_types=1);
/**
 * This file is part of the BEAR.AppMeta package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace BEAR\AppMeta;

use Koriym\Psr4List\Psr4List;

abstract class AbstractAppMeta
{
    /**
     * Application name "{Vendor}\{Project}"
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
     * @return \Generator
     */
    public function getResourceListGenerator()
    {
        $list = new Psr4List();
        $resourceListGenerator = $list($this->name . '\Resource', $this->appDir . '/src/Resource');

        return $resourceListGenerator;
    }
}
