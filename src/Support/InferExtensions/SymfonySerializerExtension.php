<?php

namespace Dedoc\Scramble\Support\InferExtensions;

use Dedoc\Scramble\Infer;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\Union;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Infers types considering Symfony Serializer annotations.
 * Handles @Groups, @Ignore, @SerializedName attributes, and custom normalizers.
 */
class SymfonySerializerExtension
{
    /** @var array<string, NormalizerInterface> */
    private array $customNormalizers = [];

    public function __construct(
        private Infer $infer
    ) {}

    /**
     * Register a custom normalizer for type inference.
     */
    public function registerNormalizer(NormalizerInterface $normalizer): void
    {
        // Store normalizer by the class name it supports
        // This is a simplified approach - real implementation might need more sophisticated matching
        $this->customNormalizers[get_class($normalizer)] = $normalizer;
    }

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

        // Check if there's a custom normalizer that handles this class
        $customType = $this->inferFromCustomNormalizer($className, $groups);
        if ($customType !== null) {
            return $customType;
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
     * Attempt to infer type from custom normalizers.
     *
     * This method analyzes the normalize() method of custom normalizers to infer
     * the structure of the normalized output.
     *
     * @param  array<string>  $groups
     */
    private function inferFromCustomNormalizer(string $className, array $groups): ?Type
    {
        foreach ($this->customNormalizers as $normalizer) {
            // Check if this normalizer supports the given class
            if (! $normalizer->supportsNormalization($className, null, [])) {
                continue;
            }

            // Try to infer from the normalize method signature/implementation
            try {
                $reflection = new ReflectionClass($normalizer);
                $normalizeMethod = $reflection->getMethod('normalize');

                // Analyze the return type of the normalize method
                $returnType = $normalizeMethod->getReturnType();
                if ($returnType instanceof \ReflectionNamedType) {
                    $typeName = $returnType->getName();

                    // If it returns an array or array-like structure, we could analyze further
                    if ($typeName === 'array') {
                        // For now, return a generic array type
                        // More sophisticated analysis could inspect the method body
                        return new \Dedoc\Scramble\Support\Type\ArrayType();
                    }

                    // If it returns a specific class, use that
                    if (class_exists($typeName)) {
                        return new ObjectType($typeName);
                    }
                }
            } catch (\ReflectionException $e) {
                // If we can't reflect on the normalizer, skip it
                continue;
            }
        }

        return null;
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
