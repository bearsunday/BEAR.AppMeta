<?php

declare(strict_types=1);

namespace BEAR\AppMeta\Exception;

use LogicException;

/**
 * The application directory is not absolute.
 *
 * Meta resolves it with realpath(), which answers against the directory the process was
 * started in, so a relative path names a different application from a different shell -
 * as does everything derived from it. Pass one that does not depend on where the caller
 * stood.
 */
final class AppDirNotAbsoluteException extends LogicException
{
}
