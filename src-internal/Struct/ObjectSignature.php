<?php

declare(strict_types=1);

namespace Struct\Reflection\Internal\Struct;

use Struct\Reflection\Internal\Struct\ObjectSignature\Method;
use Struct\Reflection\Internal\Struct\ObjectSignature\Parameter;
use Struct\Reflection\Internal\Struct\ObjectSignature\Property;

/**
 * @internal
 */
readonly class ObjectSignature
{


    /**
     * @param class-string $objectName
     * @param array<Parameter> $constructorArguments
     * @param array<Property> $properties
     * @param array<Method> $methods
     */
    public function __construct(
        public string $objectName,
        public bool $isReadOnly,
        public bool $isFinal,
        public array $constructorArguments,
        public array $properties,
        public array $methods,
    ) {
    }
}
