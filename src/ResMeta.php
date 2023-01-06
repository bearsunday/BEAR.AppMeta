<?php

declare(strict_types=1);

namespace BEAR\AppMeta;

use BEAR\Resource\ResourceObject;

final class ResMeta
{
    /** @param class-string<ResourceObject> $class */
    public function __construct(
        /**
         * URI path
         */
        public string $uriPath,
        public string $class,
        /**
         * File path
         */
        public string $filePath,
    ) {
    }
}
