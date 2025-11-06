<?php

namespace Dedoc\Scramble\Support\InferExtensions;

use Dedoc\Scramble\Infer\Extensions\Event\MethodCallEvent;
use Dedoc\Scramble\Infer\Extensions\MethodReturnTypeExtension;
use Dedoc\Scramble\Support\Type\ArrayType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\IntegerType;
use Dedoc\Scramble\Support\Type\NullType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\Union;
use Doctrine\Persistence\ObjectRepository;

/**
 * Infers return types for Doctrine repository methods.
 *
 * Handles common repository methods like find(), findOneBy(), findBy(), findAll()
 * and infers the entity type from the repository's generic type or metadata.
 */
class DoctrineRepositoryExtension implements MethodReturnTypeExtension
{
    public function shouldHandle(ObjectType $type): bool
    {
        // Handle any Doctrine repository implementations
        return is_a($type->name, ObjectRepository::class, true)
            || str_ends_with($type->name, 'Repository');
    }

    public function getMethodReturnType(MethodCallEvent $event): ?Type
    {
        $methodName = $event->getName();
        $callee = $event->getInstance();

        // Try to infer entity type from repository
        $entityType = $this->getEntityTypeFromRepository($callee);

        if (! $entityType) {
            return null;
        }

        return match ($methodName) {
            'find', 'findOneBy' => Union::wrap($entityType, new NullType),
            'findBy', 'findAll' => new ArrayType(value: $entityType),
            'count' => new IntegerType,
            default => null,
        };
    }

    /**
     * Extract entity type from repository type.
     */
    private function getEntityTypeFromRepository(Type $repositoryType): ?ObjectType
    {
        if (! $repositoryType instanceof ObjectType) {
            return null;
        }

        // If it's a Generic type like EntityRepository<User>, extract the User type
        if ($repositoryType instanceof Generic && count($repositoryType->templateTypes) > 0) {
            $firstTemplateType = $repositoryType->templateTypes[0];

            return $firstTemplateType instanceof ObjectType ? $firstTemplateType : null;
        }

        // Try to infer from repository class name (e.g., UserRepository -> User)
        if (str_ends_with($repositoryType->name, 'Repository')) {
            $entityName = substr($repositoryType->name, 0, -10); // Remove 'Repository' suffix

            // Check if entity exists
            if (class_exists($entityName)) {
                return new ObjectType($entityName);
            }

            // Try common patterns (e.g., App\Repository\UserRepository -> App\Entity\User)
            $entityName = str_replace('\\Repository\\', '\\Entity\\', $repositoryType->name);
            $entityName = substr($entityName, 0, -10); // Remove 'Repository' suffix

            if (class_exists($entityName)) {
                return new ObjectType($entityName);
            }
        }

        return null;
    }
}
