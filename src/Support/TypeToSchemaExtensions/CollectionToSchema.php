<?php

namespace Dedoc\Scramble\Support\TypeToSchemaExtensions;

use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Support\Collection;
use Dedoc\Scramble\Support\Type\ArrayType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\Type;
use Doctrine\Common\Collections\Collection as DoctrineCollection;

class CollectionToSchema extends TypeToSchemaExtension
{
    public function shouldHandle(Type $type)
    {
        if (! $type instanceof Generic) {
            return false;
        }

        // Support both custom Collection and Doctrine Collection
        return (count($type->templateTypes) === 2 && $type->isInstanceOf(Collection::class))
            || (count($type->templateTypes) >= 1 && $type->isInstanceOf(DoctrineCollection::class));
    }

    /**
     * @param  Generic  $type
     */
    public function toSchema(Type $type)
    {
        // For Doctrine Collections, the template type is typically at index 0 (value type)
        // For custom Collection, it's at index 1 (TValue)
        $valueType = $type->isInstanceOf(DoctrineCollection::class)
            ? $type->templateTypes[0]
            : $type->templateTypes[1];

        $arrayType = new ArrayType(value: $valueType);

        return $this->openApiTransformer->transform($arrayType);
    }
}
