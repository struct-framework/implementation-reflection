<?php

declare(strict_types=1);

namespace Struct\Reflection\Internal\Struct\ObjectSignature;

/**
 * @internal
 */
readonly class Value
{
    public function __construct(
        public mixed $valueData,
    ) {
    }
}
