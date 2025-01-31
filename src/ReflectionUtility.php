<?php

declare(strict_types=1);

namespace Struct\Reflection;

use Exception\Unexpected\UnexpectedException;
use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionUnionType;
use Struct\Reflection\Internal\Struct\ObjectSignature;
use Struct\Reflection\Internal\Struct\ObjectSignature\Method;
use Struct\Reflection\Internal\Struct\ObjectSignature\Parameter;
use Struct\Reflection\Internal\Struct\ObjectSignature\Parts\Attribute;
use Struct\Reflection\Internal\Struct\ObjectSignature\Parts\IntersectionType;
use Struct\Reflection\Internal\Struct\ObjectSignature\Parts\NamedType;
use Struct\Reflection\Internal\Struct\ObjectSignature\Parts\Visibility;
use Struct\Reflection\Internal\Struct\ObjectSignature\Property;
use Struct\Reflection\Internal\Struct\ObjectSignature\Value;

class ReflectionUtility
{
    /**
     * @param object|class-string $object
     */
    public static function readSignature(object|string $object): ObjectSignature
    {
        /** @var class-string $objectName */
        $objectName = $object;
        if (is_object($object) === true) {
            $objectName = $object::class;
        }
        $cacheIdentifier = MemoryCache::buildCacheIdentifier($objectName, 'aae38bab-40e9-4193-8e0b-d83154d8368c');
        if (MemoryCache::has($cacheIdentifier)) {
            return MemoryCache::read($cacheIdentifier);
        }
        $signature = self::_readSignature($objectName);
        MemoryCache::write($cacheIdentifier, $signature);
        return $signature;
    }

    protected static function _readSignature(string $objectName): ObjectSignature
    {
        try {
            $reflection = new ReflectionClass($objectName);
            // @phpstan-ignore-next-line
        } catch (ReflectionException $exception) {
            throw new UnexpectedException(1724442032, $exception);
        }
        $isReadOnly = $reflection->isReadOnly();
        $isFinal = $reflection->isFinal();
        $constructorArguments = self::readConstructorArguments($reflection);
        $properties = self::readProperties($reflection);
        $methods = self::readMethods($reflection);

        $signature = new ObjectSignature(
            $objectName,
            $isReadOnly,
            $isFinal,
            $constructorArguments,
            $properties,
            $methods,
        );
        return $signature;
    }

    /**
     * @return array<Parameter>
     */
    protected static function readConstructorArguments(ReflectionClass $reflection): array
    {
        $properties = [];
        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return $properties;
        }
        $reflectionParameters = $constructor->getParameters();
        $parameters = self::readParameters($reflectionParameters);
        return $parameters;
    }

    /**
     * @param array<ReflectionParameter> $reflectionParameters
     * @return array<Parameter>
     */
    protected static function readParameters(array $reflectionParameters): array
    {
        $parameters = [];
        foreach ($reflectionParameters as $reflectionParameter) {
            $parameter = self::buildParameter($reflectionParameter);
            $parameters[] = $parameter;
        }
        return $parameters;
    }

    /**
     * @return array<Method>
     */
    protected static function readMethods(ReflectionClass $reflection): array
    {
        $methods = [];
        $methodReflections = $reflection->getMethods();
        foreach ($methodReflections as $methodReflection) {
            $methods[] = self::readMethod($methodReflection);
        }
        return $methods;
    }

    /**
     * @return array<Method>
     */
    protected static function readMethod(ReflectionMethod $methodReflection): Method
    {
        $name = $methodReflection->getName();
        $returnTypeReflection = $methodReflection->getReturnType();
        $returnAllowsNull = false;
        $returnTypes = null;
        if ($returnTypeReflection !== null) {
            $returnTypes = self::buildTypes($returnTypeReflection);
            $returnAllowsNull = $returnTypeReflection->allowsNull();
        }

        $parameterReflections = $methodReflection->getParameters();
        $visibility = self::buildVisibility($methodReflection);
        $isStatic = $methodReflection->isStatic();
        $parameters = self::readParameters($parameterReflections);
        $attributes = self::buildAttributes($methodReflection);

        $method = new Method(
            $name,
            $returnTypes,
            $returnAllowsNull,
            $visibility,
            $isStatic,
            $parameters,
            $attributes,
        );
        return $method;
    }

    protected static function buildParameter(ReflectionParameter|ReflectionProperty $reflectionPropertyOrParameter): Parameter
    {
        $name = $reflectionPropertyOrParameter->getName();
        $type = $reflectionPropertyOrParameter->getType();
        if ($type === null) {
            throw new \Exception('The property <' . $name . '> must have an type declaration', 1738314664);
        }
        $types = self::buildTypes($type);
        $isAllowsNull = $type->allowsNull();
        $defaultValue = null;
        $isPromoted = false;
        $hasDefaultValue = false;

        if ($reflectionPropertyOrParameter instanceof ReflectionProperty === true) {
            $hasDefaultValue = $reflectionPropertyOrParameter->hasDefaultValue();
            $isPromoted = $reflectionPropertyOrParameter->isPromoted();
        }
        if ($reflectionPropertyOrParameter instanceof ReflectionParameter === true) {
            $hasDefaultValue = $reflectionPropertyOrParameter->isDefaultValueAvailable();

        }
        if($hasDefaultValue === true) {
            $valueData = $reflectionPropertyOrParameter->getDefaultValue();
            $defaultValue = new Value($valueData);
        }
        $attributes = self::buildAttributes($reflectionPropertyOrParameter);
        $parameter = new Parameter(
            $name,
            $types,
            $isPromoted,
            $isAllowsNull,
            $defaultValue,
            $attributes,
        );
        return $parameter;
    }

    /**
     * @return array<Attribute>
     */
    protected static function buildAttributes(ReflectionProperty|ReflectionParameter|ReflectionMethod $reflectionPropertyOrParameter): array
    {
        $attributes = [];
        $attributeReflections = $reflectionPropertyOrParameter->getAttributes();
        foreach ($attributeReflections as $attributeReflection) {
            $name = $attributeReflection->getName();
            $target = $attributeReflection->getTarget();
            $isRepeated = $attributeReflection->isRepeated();
            $arguments = $attributeReflection->getArguments();
            $attribute = new Attribute(
                $name,
                $target,
                $isRepeated,
                $arguments,
            );
            $attributes[] = $attribute;
        }
        return $attributes;
    }

    /**
     * @return array<Property>
     */
    protected static function readProperties(ReflectionClass $reflection): array
    {
        $properties = [];
        $propertyReflections = $reflection->getProperties();
        foreach ($propertyReflections as $propertyReflection) {
            $property = self::buildProperty($propertyReflection);
            $properties[] = $property;
        }
        return $properties;
    }

    protected static function buildProperty(ReflectionProperty $propertyReflection): Property
    {
        $parameter = self::buildParameter($propertyReflection);
        $isReadOnly = $propertyReflection->isReadOnly();
        $visibility = self::buildVisibility($propertyReflection);
        $isStatic = $propertyReflection->isStatic();
        $property = new Property(
            $parameter,
            $isReadOnly,
            $visibility,
            $isStatic,
        );
        return $property;
    }

    protected static function buildVisibility(ReflectionProperty|ReflectionMethod $reflectionPropertyOrParameter): Visibility
    {
        $visibility = null;
        if ($reflectionPropertyOrParameter->isPrivate() === true) {
            $visibility = Visibility::private;
        }
        if ($reflectionPropertyOrParameter->isPublic() === true) {
            $visibility = Visibility::public;
        }
        if ($reflectionPropertyOrParameter->isProtected() === true) {
            $visibility = Visibility::protected;
        }
        if ($visibility === null) {
            throw new UnexpectedException(1724522961);
        }
        return $visibility;
    }

    protected static function buildTypes(ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType $type): array
    {
        $propertyTypes = [];
        if ($type instanceof ReflectionNamedType === true) {
            $newPropertyTypes = self::buildFromReflectionNamed($type);
            $propertyTypes = array_merge($propertyTypes, $newPropertyTypes);
        }
        if ($type instanceof ReflectionUnionType === true) {
            $newPropertyTypes = self::buildFromUnionType($type);
            $propertyTypes = array_merge($propertyTypes, $newPropertyTypes);
        }
        if ($type instanceof ReflectionIntersectionType === true) {
            $newPropertyTypes = self::buildFromIntersectionType($type);
            $propertyTypes = array_merge($propertyTypes, $newPropertyTypes);
        }
        return $propertyTypes;
    }

    protected static function buildFromIntersectionType(ReflectionIntersectionType $type): array
    {
        $intersectionTypes = [];
        foreach ($type->getTypes() as $intersectionType) {
            if ($intersectionType instanceof ReflectionNamedType === false) {
                throw new UnexpectedException(1724439483);
            }
            $intersectionTypes[] = self::buildFromReflectionNamed($intersectionType);
        }
        $propertyType = new IntersectionType(
            $intersectionTypes,
        );
        return [$propertyType];
    }

    protected static function buildFromUnionType(ReflectionUnionType $type): array
    {
        $propertyTypes = [];
        foreach ($type->getTypes() as $unionType) {
            if ($unionType instanceof ReflectionNamedType === true) {
                $newPropertyTypes = self::buildFromReflectionNamed($unionType);
                $propertyTypes = array_merge($propertyTypes, $newPropertyTypes);
            }
            if ($unionType instanceof ReflectionIntersectionType === true) {
                $newPropertyTypes = self::buildFromIntersectionType($unionType);
                $propertyTypes = array_merge($propertyTypes, $newPropertyTypes);
            }
        }
        return $propertyTypes;
    }

    protected static function buildFromReflectionNamed(ReflectionNamedType $type): array
    {
        $propertyType = new NamedType(
            $type->getName(),
            $type->isBuiltin(),
        );
        return [$propertyType];
    }
}
