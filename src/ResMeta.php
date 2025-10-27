<?php

declare(strict_types=1);

namespace BEAR\AppMeta;

use BEAR\Resource\ResourceObject;

/**
 * @psalm-import-type UriPath from Types
 * @psalm-import-type FilePath from Types
 */
final class ResMeta
{
    /**
     * @param UriPath                      $uriPath  URI path
     * @param class-string<ResourceObject> $class    Resource class name
     * @param FilePath                     $filePath File path
     */
    public function __construct(
        public string $uriPath,
        public string $class,
        public string $filePath,
    ) {
    }
}
