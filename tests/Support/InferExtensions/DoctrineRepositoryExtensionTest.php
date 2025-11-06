<?php

use Dedoc\Scramble\Support\InferExtensions\DoctrineRepositoryExtension;

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

it('documents that DoctrineRepositoryExtension exists and has correct methods', function () {
    // This test verifies the class structure exists
    $reflection = new ReflectionClass(DoctrineRepositoryExtension::class);

    expect($reflection->hasMethod('shouldHandle'))->toBeTrue();
    expect($reflection->hasMethod('getMethodReturnType'))->toBeTrue();
});

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
 * it('infers find() return type', function () {
 *     $repository = new ObjectType(UserRepository::class);
 *     $methodCallEvent = createMethodCallEvent($repository, 'find', [...]);
 *     $returnType = $extension->getMethodReturnType($methodCallEvent);
 *     expect($returnType->toString())->toContain('User');
 *     expect($returnType->toString())->toContain('null');
 * });
 *
 * it('infers findBy() return type as array', function () {
 *     $repository = new ObjectType(UserRepository::class);
 *     $methodCallEvent = createMethodCallEvent($repository, 'findBy', [...]);
 *     $returnType = $extension->getMethodReturnType($methodCallEvent);
 *     expect($returnType->toString())->toContain('array');
 *     expect($returnType->toString())->toContain('User');
 * });
 *
 * it('infers findAll() return type as array', function () {
 *     $repository = new ObjectType(UserRepository::class);
 *     $methodCallEvent = createMethodCallEvent($repository, 'findAll', [...]);
 *     $returnType = $extension->getMethodReturnType($methodCallEvent);
 *     expect($returnType->toString())->toContain('array');
 * });
 *
 * it('infers count() return type as integer', function () {
 *     $repository = new ObjectType(UserRepository::class);
 *     $methodCallEvent = createMethodCallEvent($repository, 'count', [...]);
 *     $returnType = $extension->getMethodReturnType($methodCallEvent);
 *     expect($returnType->toString())->toBe('int');
 * });
 */
