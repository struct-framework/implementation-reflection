<?php

declare(strict_types=1);

namespace Struct\Reflection\Internal\Struct\ObjectSignature\Parts;

/**
 * @internal
 */
readonly class IntersectionType
{
    public function __construct(
        public array $namedTypes,
    ) {
    }
}
