<?php

namespace Dedoc\Scramble\Support\InferExtensions;

use Dedoc\Scramble\Infer\Extensions\Event\PropertyFetchEvent;
use Dedoc\Scramble\Infer\Extensions\PropertyTypeExtension;
use Dedoc\Scramble\Support\Doctrine\DoctrineMetadataExtractor;
use Dedoc\Scramble\Support\Type\BooleanType;
use Dedoc\Scramble\Support\Type\FloatType;
use Dedoc\Scramble\Support\Type\IntegerType;
use Dedoc\Scramble\Support\Type\NullType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\StringType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\Union;
use Dedoc\Scramble\Support\Type\UnknownType;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Infers types for Doctrine entity properties.
 *
 * Extracts field types and associations from Doctrine metadata
 * and converts them to appropriate Type objects for OpenAPI generation.
 */
class DoctrineEntityExtension implements PropertyTypeExtension
{
    public function __construct(
        private DoctrineMetadataExtractor $metadataExtractor,
    ) {}

    public function shouldHandle(ObjectType|string $type): bool
    {
        if (is_string($type)) {
            return $this->metadataExtractor->isEntity($type);
        }

        return $this->metadataExtractor->isEntity($type->name);
    }

    public function hasProperty(ObjectType $type, string $name): bool
    {
        $fieldNames = $this->metadataExtractor->getFieldNames($type->name);

        return in_array($name, $fieldNames, true);
    }

    public function getPropertyType(PropertyFetchEvent $event): ?Type
    {
        if (! $this->hasProperty($event->getInstance(), $event->getName())) {
            return null;
        }

        $className = $event->getInstance()->name;
        $propertyName = $event->getName();

        // Check if it's an association
        if ($this->metadataExtractor->isAssociation($className, $propertyName)) {
            return $this->getAssociationType($className, $propertyName);
        }

        // Regular field
        $fieldType = $this->metadataExtractor->getFieldType($className, $propertyName);

        if (! $fieldType) {
            return new UnknownType("Cannot determine type for field {$propertyName}");
        }

        $baseType = $this->mapDoctrineTypeToScrambleType($fieldType);

        // Handle nullable
        if ($this->metadataExtractor->isNullable($className, $propertyName)) {
            return Union::wrap($baseType, new NullType);
        }

        return $baseType;
    }

    /**
     * Map Doctrine field types to Scramble types.
     */
    private function mapDoctrineTypeToScrambleType(string $doctrineType): Type
    {
        return match ($doctrineType) {
            // String types
            'string', 'text', 'guid', 'ascii_string' => new StringType,

            // Integer types
            'integer', 'smallint', 'bigint' => new IntegerType,

            // Float types
            'float', 'decimal' => new FloatType,

            // Boolean
            'boolean' => new BooleanType,

            // Date/Time types - represented as strings in OpenAPI
            'date', 'time', 'datetime', 'datetimetz', 'date_immutable',
            'datetime_immutable', 'datetimetz_immutable', 'time_immutable' => new StringType,

            // JSON types - could be arrays or objects
            'json', 'json_array' => new UnknownType('JSON field type inference not fully supported'),

            // Array types
            'simple_array', 'array' => new UnknownType('Array field type inference not fully supported'),

            // Binary types
            'blob', 'binary' => new StringType,

            // Other types
            default => new UnknownType("Doctrine type '{$doctrineType}' mapping not defined"),
        };
    }

    /**
     * Get type for association properties.
     */
    private function getAssociationType(string $className, string $propertyName): Type
    {
        $mapping = $this->metadataExtractor->getAssociationMapping($className, $propertyName);

        if (! $mapping) {
            return new UnknownType("Cannot determine association type for {$propertyName}");
        }

        $targetEntity = $mapping['targetEntity'] ?? null;

        if (! $targetEntity) {
            return new UnknownType('Association target entity not found');
        }

        $targetType = new ObjectType($targetEntity);

        // Determine if it's a collection (OneToMany, ManyToMany)
        $isCollection = in_array(
            $mapping['type'] ?? null,
            [ClassMetadata::ONE_TO_MANY, ClassMetadata::MANY_TO_MANY],
            true
        );

        if ($isCollection) {
            // Return a collection type
            // For now, return the entity type wrapped in Generic
            // This could be enhanced to return Doctrine\Common\Collections\Collection<Entity>
            return new ObjectType('Doctrine\Common\Collections\Collection');
        }

        // ManyToOne, OneToOne - return single entity type
        return $targetType;
    }
}
