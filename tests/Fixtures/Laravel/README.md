# Laravel Test Fixtures

This directory contains Laravel-specific fixtures used for integration testing.

## Purpose

Scramble's core functionality includes analyzing and documenting Laravel applications,
particularly Laravel API resources and Eloquent models. These fixtures provide realistic
Laravel code patterns for testing that functionality.

## Intentional Dependencies

The following Laravel dependencies are **INTENTIONALLY** kept for testing:

### Models (`Models/` directory)
- `Illuminate\Database\Eloquent\Model` - Base model class
- `Illuminate\Database\Eloquent\Relations\*` - Relationship definitions
- `Illuminate\Database\Eloquent\SoftDeletes` - Soft delete functionality
- `Illuminate\Database\Eloquent\Builder` - Query builder
- `Illuminate\Database\Eloquent\Casts\AsEnumCollection` - Enum casting

### Enums (`Models/` directory)
- `Status` - Sample enum for post status
- `Role` - Sample enum for user roles

### Test Fixtures
- `SamplePostModel` - Example post model with relationships, scopes, and attributes
- `SampleUserModel` - Example user model with casts and relationships
- `SamplePostModelWithToArray` - Post model with custom `toArray()` method

## Not Legacy Code

These are **not** "legacy code" - they are essential test fixtures for validating
Scramble's Laravel compatibility. The models are used to test:

1. **API Documentation Generation**: Verifying that Scramble correctly documents Laravel API resources
2. **Model Analysis**: Testing Scramble's ability to infer types from Eloquent models
3. **Relationship Handling**: Ensuring relationships are properly documented
4. **Cast and Attribute Detection**: Validating that casts and custom attributes are correctly identified

## Database Schema

Tests that need database access should override the `needsDatabase()` method in their test class
and return `true`. The `SymfonyTestCase` base class will automatically set up the required
tables using Doctrine DBAL.

The following tables are automatically created when `needsDatabase()` returns true:
- `users` - For `SampleUserModel`
- `posts` - For `SamplePostModel` and `SamplePostModelWithToArray`
- `roles` - For testing role relationships

## Migration from Laravel

These fixtures were moved from `tests/Files/` to `tests/Fixtures/Laravel/Models/` as part
of the migration from Laravel to Symfony testing infrastructure. The namespace changed from:
- **Old**: `Dedoc\Scramble\Tests\Files\*`
- **New**: `Dedoc\Scramble\Tests\Fixtures\Laravel\Models\*`

All test imports have been updated accordingly.
