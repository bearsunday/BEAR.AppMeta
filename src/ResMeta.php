<?php

declare(strict_types=1);

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
