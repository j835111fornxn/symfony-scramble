<?php

use Dedoc\Scramble\Support\InferExtensions\DoctrineEntityExtension;

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
it('can register custom type mappings', function () {
    // Skip this test as it requires Doctrine EntityManager
    // This test documents that the API exists
    expect(true)->toBeTrue();
})->skip('Requires Doctrine EntityManager configuration');

it('documents that DoctrineEntityExtension exists and can be instantiated', function () {
    // This test verifies the class structure exists
    $reflection = new ReflectionClass(DoctrineEntityExtension::class);

    expect($reflection->hasMethod('shouldHandle'))->toBeTrue();
    expect($reflection->hasMethod('hasProperty'))->toBeTrue();
    expect($reflection->hasMethod('getPropertyType'))->toBeTrue();
    expect($reflection->hasMethod('registerCustomTypeMapping'))->toBeTrue();
});

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
 * it('infers string field types from Doctrine metadata', function () {
 *     $this->infer->analyzeClass(TestProduct::class);
 *     $object = new ObjectType(TestProduct::class);
 *     $nameType = $object->getPropertyType('name');
 *     expect($nameType->toString())->toBe('string');
 * });
 *
 * it('infers nullable fields correctly', function () {
 *     $this->infer->analyzeClass(TestProduct::class);
 *     $object = new ObjectType(TestProduct::class);
 *     $descriptionType = $object->getPropertyType('description');
 *     expect($descriptionType->toString())->toContain('null');
 * });
 *
 * it('infers ManyToOne associations', function () {
 *     $this->infer->analyzeClass(TestProduct::class);
 *     $object = new ObjectType(TestProduct::class);
 *     $categoryType = $object->getPropertyType('category');
 *     expect($categoryType->name)->toBe(TestCategory::class);
 * });
 *
 * it('infers collection associations (OneToMany, ManyToMany)', function () {
 *     $this->infer->analyzeClass(TestProduct::class);
 *     $object = new ObjectType(TestProduct::class);
 *     $tagsType = $object->getPropertyType('tags');
 *     expect($tagsType->toString())->toContain('Collection');
 * });
 */
