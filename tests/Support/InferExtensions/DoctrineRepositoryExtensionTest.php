<?php

namespace Dedoc\Scramble\Tests\Support\InferExtensions;

use Dedoc\Scramble\Support\InferExtensions\DoctrineRepositoryExtension;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Doctrine Repository Extension Tests
 *
 * NOTE: These are basic tests that verify the API surface of DoctrineRepositoryExtension.
 * Full integration tests require a properly configured Doctrine EntityManager with test repositories.
 *
 * The tests below verify:
 * - The extension has the correct API methods
 * - Repository method return type inference is available
 */
class DoctrineRepositoryExtensionTest extends SymfonyTestCase
{
    #[Test]
    public function documents_that_doctrine_repository_extension_exists_and_has_correct_methods(): void
    {
        // This test verifies the class structure exists
        $reflection = new \ReflectionClass(DoctrineRepositoryExtension::class);

        $this->assertTrue($reflection->hasMethod('shouldHandle'));
        $this->assertTrue($reflection->hasMethod('getMethodReturnType'));
    }

    /**
     * TODO: Integration tests with actual Doctrine repositories
     *
     * These require:
     * - A test kernel with Doctrine ORM configured
     * - Test entities and repositories
     * - Proper service registration in the test container
     *
     * Example test structure:
     *
     * #[Test]
     * public function infers_find_return_type(): void
     * {
     *     $repository = new ObjectType(UserRepository::class);
     *     $methodCallEvent = createMethodCallEvent($repository, 'find', [...]);
     *     $returnType = $extension->getMethodReturnType($methodCallEvent);
     *     $this->assertStringContainsString('User', $returnType->toString());
     *     $this->assertStringContainsString('null', $returnType->toString());
     * }
     *
     * #[Test]
     * public function infers_find_by_return_type_as_array(): void
     * {
     *     $repository = new ObjectType(UserRepository::class);
     *     $methodCallEvent = createMethodCallEvent($repository, 'findBy', [...]);
     *     $returnType = $extension->getMethodReturnType($methodCallEvent);
     *     $this->assertStringContainsString('array', $returnType->toString());
     *     $this->assertStringContainsString('User', $returnType->toString());
     * }
     *
     * #[Test]
     * public function infers_find_all_return_type_as_array(): void
     * {
     *     $repository = new ObjectType(UserRepository::class);
     *     $methodCallEvent = createMethodCallEvent($repository, 'findAll', [...]);
     *     $returnType = $extension->getMethodReturnType($methodCallEvent);
     *     $this->assertStringContainsString('array', $returnType->toString());
     * }
     *
     * #[Test]
     * public function infers_count_return_type_as_integer(): void
     * {
     *     $repository = new ObjectType(UserRepository::class);
     *     $methodCallEvent = createMethodCallEvent($repository, 'count', [...]);
     *     $returnType = $extension->getMethodReturnType($methodCallEvent);
     *     $this->assertEquals('int', $returnType->toString());
     * }
     */
}
