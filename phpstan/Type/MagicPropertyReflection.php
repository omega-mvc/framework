<?php

declare(strict_types=1);

namespace Omega\PHPStan\Type;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

/**
 * Describes a virtual property exposed through __get()/__set().
 */
final class MagicPropertyReflection implements PropertyReflection
{
    public function __construct(
        private readonly ClassReflection $declaringClass,
        private readonly Type $readableType,
        private readonly ?Type $writableType = null,
        private readonly bool $readable = true,
        private readonly bool $writable = false,
    ) {
    }

    public function getDeclaringClass(): ClassReflection
    {
        return $this->declaringClass;
    }

    public function isStatic(): bool
    {
        return false;
    }

    public function isPrivate(): bool
    {
        return false;
    }

    public function isPublic(): bool
    {
        return true;
    }

    public function getDocComment(): ?string
    {
        return null;
    }

    public function getReadableType(): Type
    {
        return $this->readableType;
    }

    public function getWritableType(): Type
    {
        return $this->writableType ?? new MixedType();
    }

    public function canChangeTypeAfterAssignment(): bool
    {
        return false;
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }

    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getDeprecatedDescription(): ?string
    {
        return null;
    }

    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
}