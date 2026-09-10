<?php

declare(strict_types=1);

namespace Omega\PHPStan\Type;

use Omega\Collection\Collection;
use Omega\Validator\Messages\Message;
use Omega\Validator\Messages\MessagePool;
use Omega\Validator\Rule\Filter;
use Omega\Validator\Rule\FilterPool;
use Omega\Validator\Rule\Valid;
use Omega\Validator\Rule\ValidPool;
use Omega\Validator\Validator;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Registers the magic properties the Validator package exposes through
 * __get()/__set(), so PHPStan can statically resolve dynamic field names.
 */
final class ValidatorMagicPropertiesClassReflectionExtension implements PropertiesClassReflectionExtension
{
    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if ($classReflection->hasNativeProperty($propertyName)) {
            return false;
        }

        return in_array($classReflection->getName(), [
            Validator::class,
            ValidPool::class,
            FilterPool::class,
            MessagePool::class,
            Collection::class,
        ], true);
    }

    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        return new MagicPropertyReflection(
            $classReflection,
            $this->resolveType($classReflection, $propertyName),
            null,
            true,
            true,
        );
    }

    private function resolveType(ClassReflection $classReflection, string $propertyName): Type
    {
        switch ($classReflection->getName()) {
            case Validator::class:
                return match ($propertyName) {
                    'errors'  => new GenericObjectType(
                        Collection::class,
                        [new StringType(), new StringType()],
                    ),
                    'filters' => new GenericObjectType(
                        Collection::class,
                        [new StringType(), new MixedType()],
                    ),
                    default   => new ObjectType(Valid::class),
                };
            case ValidPool::class:
                return new ObjectType(Valid::class);
            case FilterPool::class:
                return new ObjectType(Filter::class);
            case MessagePool::class:
                return new ObjectType(Message::class);
            case Collection::class:
                $valueType = $classReflection->getActiveTemplateTypeMap()->getType('TValue');

                return TypeCombinator::addNull($valueType ?? new MixedType());
            default:
                return new MixedType();
        }
    }
}