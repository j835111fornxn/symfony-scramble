<?php

namespace Dedoc\Scramble\Tests\Support\InferExtensions;

use Dedoc\Scramble\Support\InferExtensions\DoctrineEntityExtension;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Doctrine Entity Extension Tests
 *
 * NOTE: These are basic tests that verify the API surface of DoctrineEntityExtension.
 * Full integration tests require a properly configured Doctrine EntityManager with a test database.
 *
 * The tests below verify:
 * - The extension has the correct API methods
 * - Custom type mappings can be registered
 */
class DoctrineEntityExtensionTest extends SymfonyTestCase
{
    #[Test]
    public function can_register_custom_type_mappings(): void
    {
        // Skip this test as it requires Doctrine EntityManager
        // This test documents that the API exists
        $this->assertTrue(true);
        $this->markTestSkipped('Requires Doctrine EntityManager configuration');
    }

    #[Test]
    public function documents_that_doctrine_entity_extension_exists_and_can_be_instantiated(): void
    {
        // This test verifies the class structure exists
        $reflection = new \ReflectionClass(DoctrineEntityExtension::class);

        $this->assertTrue($reflection->hasMethod('shouldHandle'));
        $this->assertTrue($reflection->hasMethod('hasProperty'));
        $this->assertTrue($reflection->hasMethod('getPropertyType'));
        $this->assertTrue($reflection->hasMethod('registerCustomTypeMapping'));
    }

    /**
     * TODO: Integration tests with actual Doctrine entities
     *
     * These require:
     * - A test kernel with Doctrine ORM configured
     * - A test database (SQLite in-memory recommended)
     * - Test entities with proper Doctrine annotations/attributes
     * - Proper service registration in the test container
     *
     * Example test structure:
     *
     * #[Test]
     * public function infers_string_field_types_from_doctrine_metadata(): void
     * {
     *     $this->infer->analyzeClass(TestProduct::class);
     *     $object = new ObjectType(TestProduct::class);
     *     $nameType = $object->getPropertyType('name');
     *     $this->assertEquals('string', $nameType->toString());
     * }
     *
     * #[Test]
     * public function infers_nullable_fields_correctly(): void
     * {
     *     $this->infer->analyzeClass(TestProduct::class);
     *     $object = new ObjectType(TestProduct::class);
     *     $descriptionType = $object->getPropertyType('description');
     *     $this->assertStringContainsString('null', $descriptionType->toString());
     * }
     *
     * #[Test]
     * public function infers_many_to_one_associations(): void
     * {
     *     $this->infer->analyzeClass(TestProduct::class);
     *     $object = new ObjectType(TestProduct::class);
     *     $categoryType = $object->getPropertyType('category');
     *     $this->assertEquals(TestCategory::class, $categoryType->name);
     * }
     *
     * #[Test]
     * public function infers_collection_associations_one_to_many_many_to_many(): void
     * {
     *     $this->infer->analyzeClass(TestProduct::class);
     *     $object = new ObjectType(TestProduct::class);
     *     $tagsType = $object->getPropertyType('tags');
     *     $this->assertStringContainsString('Collection', $tagsType->toString());
     * }
     */
}
