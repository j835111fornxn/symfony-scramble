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
     * @param  class-string  $className
     * @param  string[]|null  $groups  Validation groups to filter by (null means all groups)
     * @return array<string, Constraint[]> Property name => constraints
     */
    public function extractFromClass(string $className, ?array $groups = null): array
    {
        if (! class_exists($className)) {
            return [];
        }

        try {
            $metadata = $this->validator->getMetadataFor($className);
        } catch (\Exception $e) {
            return [];
        }

        if (! $metadata instanceof ClassMetadataInterface) {
            return [];
        }

        $constraints = [];

        // Extract property constraints
        foreach ($metadata->getConstrainedProperties() as $propertyName) {
            $propertyMetadata = $metadata->getPropertyMetadata($propertyName);

            foreach ($propertyMetadata as $propMeta) {
                $propertyConstraints = $propMeta->getConstraints();

                // Filter constraints by validation groups if specified
                if ($groups !== null) {
                    $propertyConstraints = $this->filterConstraintsByGroups($propertyConstraints, $groups);
                }

                if (! empty($propertyConstraints)) {
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
     * @param  class-string  $className
     * @param  string[]|null  $groups  Validation groups to filter by (null means all groups)
     * @return Constraint[]
     */
    public function extractFromProperty(string $className, string $propertyName, ?array $groups = null): array
    {
        if (! class_exists($className)) {
            return [];
        }

        try {
            $metadata = $this->validator->getMetadataFor($className);
        } catch (\Exception $e) {
            return [];
        }

        if (! $metadata instanceof ClassMetadataInterface) {
            return [];
        }

        $propertyMetadata = $metadata->getPropertyMetadata($propertyName);
        $constraints = [];

        foreach ($propertyMetadata as $propMeta) {
            $propertyConstraints = $propMeta->getConstraints();

            // Filter constraints by validation groups if specified
            if ($groups !== null) {
                $propertyConstraints = $this->filterConstraintsByGroups($propertyConstraints, $groups);
            }

            $constraints = array_merge($constraints, $propertyConstraints);
        }

        return $constraints;
    }

    /**
     * Check if a class has validation constraints.
     *
     * @param  class-string  $className
     * @param  string[]|null  $groups  Validation groups to check (null means any group)
     */
    public function hasConstraints(string $className, ?array $groups = null): bool
    {
        if (! class_exists($className)) {
            return false;
        }

        try {
            $metadata = $this->validator->getMetadataFor($className);
        } catch (\Exception $e) {
            return false;
        }

        if (! $metadata instanceof ClassMetadataInterface) {
            return false;
        }

        if (empty($metadata->getConstrainedProperties())) {
            return false;
        }

        // If no groups specified, any constraint is valid
        if ($groups === null) {
            return true;
        }

        // Check if any property has constraints for the specified groups
        foreach ($metadata->getConstrainedProperties() as $propertyName) {
            $propertyMetadata = $metadata->getPropertyMetadata($propertyName);
            foreach ($propertyMetadata as $propMeta) {
                $constraints = $propMeta->getConstraints();
                if (! empty($this->filterConstraintsByGroups($constraints, $groups))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Filter constraints by validation groups.
     *
     * @param  Constraint[]  $constraints
     * @param  string[]  $groups
     * @return Constraint[]
     */
    private function filterConstraintsByGroups(array $constraints, array $groups): array
    {
        return array_filter($constraints, function (Constraint $constraint) use ($groups) {
            // If constraint has no groups specified, it belongs to 'Default' group
            $constraintGroups = $constraint->groups ?? ['Default'];

            // Check if any of the requested groups match the constraint's groups
            return ! empty(array_intersect($groups, $constraintGroups));
        });
    }
}
