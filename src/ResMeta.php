<?php
declare(strict_types=1);
/**
 * This file is part of the BEAR.AppMeta package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace BEAR\AppMeta;

final class ResMeta
{
    /**
     * URI path
     *
     * @var string
     */
    public $uriPath;

    /**
     * Class name
     *
     * @var string
     */
    public $class;

    /**
     * File path
     *
     * @var string
     */
    public $filePath;

    public function __construct(string $uriPath, string $class, string $filePath)
    {
        $this->uriPath = $uriPath;
        $this->class = $class;
        $this->filePath = $filePath;
    }
}
