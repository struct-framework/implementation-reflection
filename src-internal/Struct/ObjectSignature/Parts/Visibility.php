<?php

declare(strict_types=1);

namespace Struct\Reflection\Internal\Struct\ObjectSignature\Parts;

/**
 * @internal
 */
enum Visibility
{
    case public;
    case protected;
    case private;
}
