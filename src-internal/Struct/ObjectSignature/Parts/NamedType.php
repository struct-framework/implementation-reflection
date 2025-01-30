<?php

declare(strict_types=1);

namespace Struct\Reflection\Internal\Struct\ObjectSignature\Parts;

/**
 * @internal
 */
readonly class NamedType
{
    public function __construct(
        public string $dataType,
        public bool $isBuiltin,
    ) {
    }
}
