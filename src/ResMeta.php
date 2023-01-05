<?php

declare(strict_types=1);

namespace BEAR\AppMeta;

final class ResMeta
{
    /**
     * @param class-string $class
     */
    public function __construct(
        /**
         * URI path
         */
        public string $uriPath,
        public string $class,
        /**
         * File path
         */
        public string $filePath
    ) {
    }
}
