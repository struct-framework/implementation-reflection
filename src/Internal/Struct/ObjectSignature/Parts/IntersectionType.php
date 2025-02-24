<?php

declare(strict_types=1);

namespace Struct\Reflection\Internal\Struct\ObjectSignature\Parts;

use Struct\Attribute\ArrayList;

/**
 * @internal
 */
readonly class IntersectionType
{
    /**
     * @param array<NamedType> $namedTypes
     */
    public function __construct(
        #[ArrayList(NamedType::class)]
        public array $namedTypes,
    ) {
    }
}
