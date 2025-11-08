# Design: Eliminate Laravel Dependencies from Tests

## Overview

This design outlines the strategy for removing or abstracting Laravel dependencies from the test suite while maintaining test coverage and functionality. The approach is phased to minimize risk and allow incremental progress.

## Current State Analysis

### Laravel Dependencies in Tests (40 files affected)

1. **HTTP/Routing (21 files)**: ✅ Already migrated to Symfony
2. **Foundation Traits (2 files)**: Can be removed immediately
3. **Auth/Authorization (3 files)**: Can be replaced with Symfony Security
4. **Database/Migrations (16 files)**: Need fixture strategy
5. **JsonResource (14 files)**: Core feature - needs abstraction or acceptance as intentional dependency
6. **Validation/FormRequest (7 files)**: Core feature - needs abstraction or acceptance
7. **Pagination (5 files)**: Core feature - needs abstraction or acceptance

## Architectural Decisions

### Decision 1: Three-Tier Strategy

**Options Considered:**
1. **Remove all Laravel dependencies completely**: Not feasible - Scramble's purpose includes documenting Laravel APIs
2. **Keep all Laravel dependencies as-is**: Against project migration goals
3. **Three-tier approach**: Separate into removable, abstractable, and intentional dependencies

**Decision:** Three-tier approach

**Rationale:**
- **Tier 1 (Remove)**: Dependencies that are purely convenience or can be directly replaced
- **Tier 2 (Abstract)**: Core features that should support multiple frameworks
- **Tier 3 (Accept)**: Intentional integration testing with Laravel components

### Decision 2: Test Fixture Strategy for Models

**Options Considered:**
1. **Remove all Eloquent models**: Would break API documentation generation tests
2. **Convert to Doctrine entities**: Changes the nature of what we're testing
3. **Keep as documented test fixtures**: Maintain but clearly mark as intentional

**Decision:** Keep as documented test fixtures in isolated directory

**Rationale:**
- Scramble's core value includes analyzing Laravel Eloquent models
- These models test real-world Laravel patterns
- They should be treated as "fixture data" rather than "test dependencies"
- Moving them to `tests/Fixtures/Laravel/` makes the intent clear

### Decision 3: Migration Handling

**Options Considered:**
1. **Convert to Doctrine migrations**: Heavyweight for simple test schemas
2. **Use SQL fixture files**: Lightweight but less type-safe
3. **In-memory array fixtures**: Simple but limited functionality

**Decision:** Use Doctrine DBAL schema builder with SQL fixtures

**Rationale:**
- Doctrine DBAL provides schema building without full ORM
- Maintains type safety and IDE support
- Compatible with Symfony ecosystem
- Avoids Laravel dependency while keeping similar developer experience

### Decision 4: Validation & Pagination Abstraction

**Options Considered:**
1. **Full abstraction layer now**: High upfront cost
2. **Keep Laravel-specific**: Against migration goals
3. **Phased abstraction**: Start with interfaces, implement later

**Decision:** Phased abstraction approach

**Rationale:**
- Define interfaces for validation rule extraction
- Define interfaces for pagination response handling
- Laravel implementation becomes one adapter
- Symfony implementation can be added incrementally
- Tests work with interfaces, not concrete implementations

## Implementation Strategy

### Phase 1: Immediate Removals (1-2 days)

#### 1.1 Remove Foundation Traits
**Files affected:**
- `tests/ErrorsResponsesTest.php`

**Approach:**
```php
// Before
class TestController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function adds_authorization_error_response(Request $request)
    {
        $this->authorize('viewAny', User::class);
    }
}

// After
class TestController
{
    public function adds_authorization_error_response(Request $request, AuthorizationCheckerInterface $authChecker)
    {
        if (!$authChecker->isGranted('VIEW', 'User')) {
            throw new AccessDeniedException();
        }
    }
}
```

**Testing:**
- Verify error response tests still pass
- Ensure exception types are correctly documented

#### 1.2 Replace Auth Exceptions
**Files affected:**
- `tests/ErrorsResponsesTest.php`
- `tests/Support/ExceptionToResponseExtensions/CustomExceptionToResponseExtensionTest.php`

**Approach:**
```php
// Before
use Illuminate\Auth\AuthenticationException;
throw new AuthenticationException();

// After
use Symfony\Component\Security\Core\Exception\AuthenticationException;
throw new AuthenticationException('Unauthenticated');
```

**Testing:**
- Verify exception-to-response mapping still works
- Check OpenAPI documentation generation for auth errors

### Phase 2: Database Migrations (3-5 days)

#### 2.1 Create Migration Abstraction
**New file:** `tests/Database/TestSchemaBuilder.php`

```php
namespace Dedoc\Scramble\Tests\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

class TestSchemaBuilder
{
    public function __construct(private Connection $connection) {}

    public function createPostsTable(): void
    {
        $schema = new Schema();
        $table = $schema->createTable('posts');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('title', 'string', ['length' => 255]);
        $table->addColumn('content', 'text', ['notnull' => false]);
        $table->addColumn('user_id', 'integer');
        $table->addColumn('approved_at', 'datetime', ['notnull' => false]);
        $table->addColumn('deleted_at', 'datetime', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);

        $queries = $schema->toSql($this->connection->getDatabasePlatform());
        foreach ($queries as $query) {
            $this->connection->executeStatement($query);
        }
    }

    public function createUsersTable(): void { /* ... */ }
    public function createRolesTable(): void { /* ... */ }
}
```

**Update SymfonyTestCase:**
```php
protected function setUp(): void
{
    parent::setUp();

    if ($this->needsDatabase()) {
        $schemaBuilder = new TestSchemaBuilder(
            static::getContainer()->get(Connection::class)
        );
        $schemaBuilder->createUsersTable();
        $schemaBuilder->createPostsTable();
        $schemaBuilder->createRolesTable();
    }
}

protected function needsDatabase(): bool
{
    return false; // Override in tests that need DB
}
```

#### 2.2 Move Eloquent Models to Fixtures
**Directory structure:**
```
tests/
  Fixtures/
    Laravel/
      Models/
        SamplePostModel.php
        SampleUserModel.php
        SamplePostModelWithToArray.php
      README.md  # Explains these are intentional Laravel fixtures
  Database/
    TestSchemaBuilder.php
```

**README.md content:**
```markdown
# Laravel Test Fixtures

This directory contains Laravel-specific fixtures used for integration testing.

## Purpose

Scramble's core functionality includes analyzing and documenting Laravel applications,
particularly Laravel API resources and Eloquent models. These fixtures provide realistic
Laravel code patterns for testing that functionality.

## Intentional Dependencies

The following Laravel dependencies are INTENTIONALLY kept for testing:
- `Illuminate\Database\Eloquent\Model` - Base model class
- `Illuminate\Database\Eloquent\Relations\*` - Relationship definitions
- `Illuminate\Http\Resources\Json\JsonResource` - API resource transformation

These are not "legacy code" - they are essential test fixtures for validating
Scramble's Laravel compatibility.
```

### Phase 3: Validation & Pagination Abstraction (1-2 weeks)

#### 3.1 Define Validation Abstraction
**New file:** `src/Support/Validation/ValidationRuleExtractorInterface.php`

```php
namespace Dedoc\Scramble\Support\Validation;

interface ValidationRuleExtractorInterface
{
    /**
     * Extract validation rules from a request class or method.
     */
    public function extract(string $className, string $method = null): array;

    /**
     * Convert framework-specific rules to OpenAPI parameters.
     */
    public function toOpenApiParameters(array $rules): array;
}
```

**Implementations:**
- `LaravelValidationRuleExtractor` - Current implementation
- `SymfonyValidationRuleExtractor` - Future implementation

#### 3.2 Define Pagination Abstraction
**New file:** `src/Support/Pagination/PaginationTypeInterface.php`

```php
namespace Dedoc\Scramble\Support\Pagination;

interface PaginationTypeInterface
{
    public function getMetaSchema(): array;
    public function getLinksSchema(): array;
    public function supports(string $className): bool;
}
```

**Implementations:**
- `LaravelPaginatorType` - For Laravel Paginator/LengthAwarePaginator/CursorPaginator
- `SymfonyPaginatorType` - Future implementation for Pagerfanta or similar

## Testing Strategy

### Test Coverage Requirements
- All existing tests must pass after each phase
- No reduction in code coverage
- Integration tests for abstraction layers

### Test Execution
```bash
# Phase 1 validation
vendor/bin/phpunit --testsuite unit
vendor/bin/phpunit --testsuite integration

# Phase 2 validation
vendor/bin/phpunit tests/Support/InferExtensions/ModelExtensionTest.php
vendor/bin/phpunit tests/Files/

# Phase 3 validation
vendor/bin/phpunit tests/ValidationRulesDocumentingTest.php
vendor/bin/phpunit tests/Support/TypeToSchemaExtensions/PaginatorTypeToSchemaTest.php
```

## Migration Checklist

### Pre-Migration
- [ ] Ensure all tests pass with current implementation
- [ ] Document current test coverage percentage
- [ ] Back up existing test snapshots

### Phase 1 Execution
- [ ] Remove Foundation traits from controllers
- [ ] Replace Laravel auth exceptions with Symfony equivalents
- [ ] Update related tests
- [ ] Verify all tests pass
- [ ] Update documentation

### Phase 2 Execution
- [ ] Create TestSchemaBuilder with Doctrine DBAL
- [ ] Convert migrations one by one
- [ ] Move Eloquent models to Fixtures/Laravel/
- [ ] Add README explaining intentional fixtures
- [ ] Update test imports
- [ ] Verify database-dependent tests pass

### Phase 3 Execution
- [ ] Define ValidationRuleExtractorInterface
- [ ] Refactor existing Laravel implementation to adapter
- [ ] Define PaginationTypeInterface
- [ ] Refactor existing pagination types to adapters
- [ ] Update tests to use interfaces
- [ ] Document abstraction architecture

## Rollback Plan

Each phase is independent:
- **Phase 1**: Simple replacements - rollback via git revert
- **Phase 2**: Schema builder is additive - can run both systems in parallel during migration
- **Phase 3**: Abstraction is backwards compatible - existing code continues to work

## Success Metrics

- Test suite passes 100%
- Code coverage maintained or improved
- Clear documentation of intentional Laravel dependencies
- Abstraction interfaces defined and documented
- CI/CD pipeline updated and passing
