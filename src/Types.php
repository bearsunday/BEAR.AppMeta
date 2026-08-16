<?php

declare(strict_types=1);

namespace BEAR\AppMeta;

/**
 * Type definitions for BEAR.AppMeta
 *
 * @phpcs:disable SlevomatCodingStandard.Commenting.DocCommentSpacing
 *
 * Domain Types
 * @psalm-type AppName = non-empty-string
 * @psalm-type Context = non-empty-string
 * @psalm-type AppDir = non-empty-string
 * @psalm-type TmpDir = non-empty-string
 * @psalm-type LogDir = non-empty-string
 * @psalm-type WriteDir = non-empty-string
 * @psalm-type UriPath = non-empty-string
 * @psalm-type FilePath = non-empty-string
 * @psalm-type Scheme = 'app'|'page'|'*'
 */
final class Types
{
    /** @codeCoverageIgnore */
    private function __construct()
    {
    }
}
