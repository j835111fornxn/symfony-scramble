<?php

namespace Dedoc\Scramble\Support\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Extracts Doctrine entity metadata for OpenAPI documentation.
 *
 * Provides access to entity field types, associations, nullability,
 * and other metadata needed for schema generation.
 */
class DoctrineMetadataExtractor
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    /**
     * Check if a class is a Doctrine entity.
     *
     * @param class-string $className
     */
    public function isEntity(string $className): bool
    {
        if (!class_exists($className)) {
            return false;
        }

        try {
            $this->entityManager->getMetadataFactory()->getMetadataFor($className);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get entity metadata.
     *
     * @param class-string $className
     */
    public function getMetadata(string $className): ?ClassMetadata
    {
        if (!$this->isEntity($className)) {
            return null;
        }

        try {
            $metadata = $this->entityManager->getMetadataFactory()->getMetadataFor($className);
            return $metadata instanceof ClassMetadata ? $metadata : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get all field names for an entity.
     *
     * @param class-string $className
     * @return string[]
     */
    public function getFieldNames(string $className): array
    {
        $metadata = $this->getMetadata($className);

        if (!$metadata) {
            return [];
        }

        return array_merge(
            $metadata->getFieldNames(),
            $metadata->getAssociationNames()
        );
    }

    /**
     * Get field type for a property.
     *
     * @param class-string $className
     */
    public function getFieldType(string $className, string $fieldName): ?string
    {
        $metadata = $this->getMetadata($className);

        if (!$metadata) {
            return null;
        }

        if ($metadata->hasField($fieldName)) {
            return $metadata->getTypeOfField($fieldName);
        }

        if ($metadata->hasAssociation($fieldName)) {
            return $metadata->getAssociationTargetClass($fieldName);
        }

        return null;
    }

    /**
     * Check if a field is nullable.
     *
     * @param class-string $className
     */
    public function isNullable(string $className, string $fieldName): bool
    {
        $metadata = $this->getMetadata($className);

        if (!$metadata || !$metadata->hasField($fieldName)) {
            return false;
        }

        $fieldMapping = $metadata->getFieldMapping($fieldName);

        return $fieldMapping['nullable'] ?? false;
    }

    /**
     * Check if a property is an association.
     *
     * @param class-string $className
     */
    public function isAssociation(string $className, string $fieldName): bool
    {
        $metadata = $this->getMetadata($className);

        if (!$metadata) {
            return false;
        }

        return $metadata->hasAssociation($fieldName);
    }

    /**
     * Get association mapping info.
     *
     * @param class-string $className
     * @return array<string, mixed>|null
     */
    public function getAssociationMapping(string $className, string $fieldName): ?array
    {
        $metadata = $this->getMetadata($className);

        if (!$metadata || !$metadata->hasAssociation($fieldName)) {
            return null;
        }

        $mapping = $metadata->getAssociationMapping($fieldName);

        // Convert AssociationMapping object to array for compatibility
        return is_array($mapping) ? $mapping : (array) $mapping;
    }

    /**
     * Get identifier field name(s).
     *
     * @param class-string $className
     * @return string[]
     */
    public function getIdentifierFieldNames(string $className): array
    {
        $metadata = $this->getMetadata($className);

        if (!$metadata) {
            return [];
        }

        return $metadata->getIdentifierFieldNames();
    }
}
