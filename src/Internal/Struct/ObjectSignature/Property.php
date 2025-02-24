<?php

declare(strict_types=1);

namespace Struct\Reflection\Internal\Struct\ObjectSignature;

use Struct\Reflection\Internal\Struct\ObjectSignature\Parts\Visibility;

/**
 * @internal
 */
readonly class Property
{
    public function __construct(
        public Parameter $parameter,
        public ?bool $isReadOnly,
        public ?Visibility $visibility,
        public ?bool $isStatic,
    ) {
    }
}
