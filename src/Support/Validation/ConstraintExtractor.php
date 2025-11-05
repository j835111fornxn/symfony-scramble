<?php

namespace Dedoc\Scramble\Support\Validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Mapping\ClassMetadataInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Extracts Symfony Validator constraints from classes, properties, and methods.
 *
 * This service analyzes entities, DTOs, and form types to extract validation
 * constraints that can be converted to OpenAPI schema properties.
 */
class ConstraintExtractor
{
    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {}

    /**
     * Extract all constraints for a class.
     *
     * @param class-string $className
     * @return array<string, Constraint[]> Property name => constraints
     */
    public function extractFromClass(string $className): array
    {
        if (!class_exists($className)) {
            return [];
        }

        try {
            $metadata = $this->validator->getMetadataFor($className);
        } catch (\Exception $e) {
            return [];
        }

        if (!$metadata instanceof ClassMetadataInterface) {
            return [];
        }

        $constraints = [];

        // Extract property constraints
        foreach ($metadata->getConstrainedProperties() as $propertyName) {
            $propertyMetadata = $metadata->getPropertyMetadata($propertyName);

            foreach ($propertyMetadata as $metadata) {
                $propertyConstraints = $metadata->getConstraints();

                if (!empty($propertyConstraints)) {
                    $constraints[$propertyName] = array_merge(
                        $constraints[$propertyName] ?? [],
                        $propertyConstraints
                    );
                }
            }
        }

        return $constraints;
    }

    /**
     * Extract constraints for a specific property.
     *
     * @param class-string $className
     * @return Constraint[]
     */
    public function extractFromProperty(string $className, string $propertyName): array
    {
        if (!class_exists($className)) {
            return [];
        }

        try {
            $metadata = $this->validator->getMetadataFor($className);
        } catch (\Exception $e) {
            return [];
        }

        if (!$metadata instanceof ClassMetadataInterface) {
            return [];
        }

        $propertyMetadata = $metadata->getPropertyMetadata($propertyName);
        $constraints = [];

        foreach ($propertyMetadata as $metadata) {
            $constraints = array_merge($constraints, $metadata->getConstraints());
        }

        return $constraints;
    }

    /**
     * Check if a class has validation constraints.
     *
     * @param class-string $className
     */
    public function hasConstraints(string $className): bool
    {
        if (!class_exists($className)) {
            return false;
        }

        try {
            $metadata = $this->validator->getMetadataFor($className);
        } catch (\Exception $e) {
            return false;
        }

        if (!$metadata instanceof ClassMetadataInterface) {
            return false;
        }

        return !empty($metadata->getConstrainedProperties());
    }
}
