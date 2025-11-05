<?php

namespace Dedoc\Scramble\Support\InferExtensions;

use Dedoc\Scramble\Infer;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\Union;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Infers types considering Symfony Serializer annotations.
 * Handles @Groups, @Ignore, @SerializedName attributes.
 */
class SymfonySerializerExtension
{
    public function __construct(
        private Infer $infer
    ) {}

    /**
     * Get type for a class considering serialization groups.
     *
     * @param  array<string>  $groups
     */
    public function getSerializedType(string $className, array $groups = []): ?Type
    {
        if (! class_exists($className)) {
            return null;
        }

        try {
            $reflection = new ReflectionClass($className);
        } catch (\ReflectionException $e) {
            return null;
        }

        $properties = [];

        foreach ($reflection->getProperties() as $property) {
            // Check if property should be ignored
            if ($this->shouldIgnoreProperty($property)) {
                continue;
            }

            // Check if property is in the requested groups
            if (! empty($groups) && ! $this->isInGroups($property, $groups)) {
                continue;
            }

            // Get the serialized name (might be different from property name)
            $serializedName = $this->getSerializedName($property);

            // Infer the property type
            $type = $this->inferPropertyType($property);

            if ($type) {
                $properties[$serializedName] = $type;
            }
        }

        // Return an ObjectType
        // Properties metadata could be stored for later use
        return new ObjectType($className);
    }

    /**
     * Check if property should be ignored during serialization.
     */
    private function shouldIgnoreProperty(ReflectionProperty $property): bool
    {
        // Check for Symfony Serializer Ignore attribute
        $attributes = $property->getAttributes(Ignore::class);

        return ! empty($attributes);
    }

    /**
     * Check if property belongs to any of the specified groups.
     */
    private function isInGroups(ReflectionProperty $property, array $requestedGroups): bool
    {
        $attributes = $property->getAttributes(Groups::class);

        if (empty($attributes)) {
            // Properties without groups are included in all serializations
            return true;
        }

        foreach ($attributes as $attribute) {
            /** @var Groups $groupsAnnotation */
            $groupsAnnotation = $attribute->newInstance();
            $propertyGroups = $groupsAnnotation->getGroups();

            // Check if any of the property's groups match the requested groups
            foreach ($propertyGroups as $group) {
                if (in_array($group, $requestedGroups, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the serialized name for a property.
     */
    private function getSerializedName(ReflectionProperty $property): string
    {
        $attributes = $property->getAttributes(SerializedName::class);

        if (! empty($attributes)) {
            /** @var SerializedName $serializedNameAttr */
            $serializedNameAttr = $attributes[0]->newInstance();

            return $serializedNameAttr->getSerializedName();
        }

        return $property->getName();
    }

    /**
     * Infer type for a property.
     */
    private function inferPropertyType(ReflectionProperty $property): ?Type
    {
        $type = $property->getType();

        if (! $type) {
            return null;
        }

        // Convert ReflectionType to Scramble Type
        return $this->reflectionTypeToScrambleType($type);
    }

    /**
     * Convert PHP ReflectionType to Scramble Type.
     */
    private function reflectionTypeToScrambleType(\ReflectionType $type): ?Type
    {
        if ($type instanceof \ReflectionNamedType) {
            $typeName = $type->getName();

            $scrambleType = match ($typeName) {
                'string' => new \Dedoc\Scramble\Support\Type\StringType,
                'int' => new \Dedoc\Scramble\Support\Type\IntegerType,
                'float' => new \Dedoc\Scramble\Support\Type\FloatType,
                'bool' => new \Dedoc\Scramble\Support\Type\BooleanType,
                'array' => new \Dedoc\Scramble\Support\Type\ArrayType,
                'mixed' => new \Dedoc\Scramble\Support\Type\MixedType,
                default => class_exists($typeName) || interface_exists($typeName)
                    ? new ObjectType($typeName)
                    : null,
            };

            if ($scrambleType && $type->allowsNull()) {
                return Union::wrap($scrambleType, new \Dedoc\Scramble\Support\Type\NullType);
            }

            return $scrambleType;
        }

        if ($type instanceof \ReflectionUnionType) {
            $types = [];
            foreach ($type->getTypes() as $subType) {
                $scrambleType = $this->reflectionTypeToScrambleType($subType);
                if ($scrambleType) {
                    $types[] = $scrambleType;
                }
            }

            return Union::wrap($types);
        }

        return null;
    }
}
