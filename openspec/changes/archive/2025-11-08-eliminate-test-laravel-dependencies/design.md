# Design: Eliminate Test Laravel Dependencies

## Architecture Overview
This change focuses on removing Laravel framework dependencies from the test suite while maintaining test coverage and functionality. The approach uses Doctrine entities for data models and Symfony serialization for API response testing.

## Strategy

### 1. Laravel Model Replacement

#### Current State
Tests reference Laravel Eloquent models that no longer exist:
```php
use Dedoc\Scramble\Tests\Fixtures\Laravel\Models\SamplePostModel;
use Dedoc\Scramble\Tests\Fixtures\Laravel\Models\SampleUserModel;
```

#### Target State
Use Doctrine entities with proper test fixtures:
```php
use Dedoc\Scramble\Tests\Fixtures\Entities\Post;
use Dedoc\Scramble\Tests\Fixtures\Entities\User;
```

#### Implementation Options

**Option A: Full Doctrine Entities** (Recommended)
- Create proper Doctrine entities with attributes
- Provides realistic test data matching production
- Allows testing of DoctrineEntityExtension properly
- Requires Doctrine ORM configuration in tests

**Option B: Simple Test Doubles**
- Create plain PHP classes without ORM annotations
- Faster to implement
- May not test real-world scenarios
- Doesn't validate Doctrine integration

**Decision: Use Option A** - Better validates the actual system behavior

### 2. JsonResource Test Strategy

#### Current Situation
- `tests/Stubs/JsonResource.php` provides minimal Laravel JsonResource functionality
- Tests like `InferTypesTest::test_gets_json_resource_type` expect JsonResource behavior
- Scramble originally had `JsonResourceTypeToSchema` extension (removed during migration)

#### Options

**Option A: Remove JsonResource Tests**
- Simplest approach
- JsonResource is Laravel-specific, not relevant to Symfony
- Tests don't add value to Symfony-based system

**Option B: Replace with Symfony Serializer**
- Create equivalent tests using Symfony's Serializer component
- Tests similar concepts (API resource transformation)
- Requires rewriting test logic

**Option C: Keep Stub, Improve Implementation**
- Enhance the stub to fully mimic Laravel's JsonResource
- Maintains test coverage
- Still maintains some Laravel coupling

**Decision: Use Option A for now** - JsonResource is Laravel-specific. If resource transformation testing is needed, create new tests using Symfony serializer patterns.

### 3. Test Fixture Organization

#### Directory Structure
```
tests/
  Fixtures/
    Entities/           # Doctrine entities for testing
      User.php
      Post.php
      Comment.php
    Controllers/        # Test controllers (existing)
    Forms/             # Test forms (existing)
```

#### Entity Design Pattern
```php
namespace Dedoc\Scramble\Tests\Fixtures\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $email;

    // Getters and setters...
}
```

### 4. Test Update Pattern

#### Before (Laravel)
```php
public function test_infers_model_type(): void
{
    $type = $this->infer->analyzeClass(SampleUserModel::class);

    $schema = $this->typeTransformer->transform($type);

    $this->assertMatchesSnapshot($schema->toArray());
}
```

#### After (Doctrine)
```php
public function test_infers_entity_type(): void
{
    $type = $this->infer->analyzeClass(User::class);

    $schema = $this->typeTransformer->transform($type);

    $this->assertMatchesSnapshot($schema->toArray());
}
```

### 5. Snapshot Management

#### Strategy
1. **Identify affected snapshots**: Find snapshots referencing `Illuminate\*` or `Laravel\*`
2. **Selective regeneration**: Only regenerate snapshots for tests we're actively fixing
3. **Manual verification**: Review each regenerated snapshot to ensure correctness
4. **Version control**: Commit snapshot changes separately for easy review

#### Snapshot Update Process
```bash
# 1. Run test and let it fail
php vendor/bin/phpunit tests/InferTypesTest.php::test_infers_entity_type

# 2. Review the diff
git diff tests/__snapshots__/

# 3. If correct, commit
git add tests/__snapshots__/
```

## Technical Decisions

### Decision 1: Create Minimal Doctrine Entities
**Rationale**: Tests only need simple entities to validate type inference, not full domain models

**Trade-offs**:
- Simpler: Faster to implement
- Realistic: Still uses real Doctrine annotations
- Limited: May not catch edge cases in complex entity relationships

### Decision 2: Remove JsonResource Tests Entirely
**Rationale**: JsonResource is Laravel-specific and no longer relevant

**Trade-offs**:
- Clean: Removes Laravel dependency completely
- Coverage loss: Loses some API resource transformation tests
- Acceptable: Can add Symfony-equivalent tests later if needed

### Decision 3: Delete Backup Files Immediately
**Rationale**: Backup files are no longer needed and clutter the repository

**Trade-offs**:
- Clean: Clearer test directory
- Risk: Minimal, changes are in git history
- Benefit: Reduces confusion

### Decision 4: Progressive Test Fixing
**Rationale**: Fix tests incrementally rather than all at once

**Strategy**:
1. Fix critical path tests first (type inference, OpenAPI generation)
2. Mark non-critical tests as skipped temporarily
3. Tackle remaining tests in subsequent iterations

**Trade-offs**:
- Pragmatic: Delivers value faster
- Incomplete: Some tests remain broken temporarily
- Manageable: Easier to review and validate changes

## Implementation Guidelines

### Creating Test Entities

1. **Keep entities simple**: Only add properties needed for tests
2. **Use Doctrine attributes**: Modern attribute syntax, not annotations
3. **Include getters/setters**: Tests may need to access/modify properties
4. **Document purpose**: Add comments explaining what each entity tests

### Updating Tests

1. **One test file at a time**: Easier to validate
2. **Run tests after each change**: Catch issues early
3. **Update snapshots carefully**: Verify they reflect correct behavior
4. **Keep test intent**: Don't change what the test validates, only how

### Handling Failing Tests

1. **Categorize failures**:
   - Laravel dependency → Fix by removing dependency
   - Logic error → Fix the logic
   - Outdated snapshot → Regenerate snapshot
   - Unrelated issue → File separate issue, skip test temporarily

2. **Skip pattern**:
   ```php
   #[Test]
   public function it_does_something(): void
   {
       $this->markTestSkipped('TODO: Needs Doctrine entity fixture');
       // ... rest of test
   }
   ```

## Migration Checklist

- [ ] Create `tests/Fixtures/Entities/` directory
- [ ] Implement User entity
- [ ] Implement Post entity
- [ ] Update `InferTypesTest.php` to use entities
- [ ] Remove JsonResource tests or mark as skipped
- [ ] Delete backup files
- [ ] Regenerate affected snapshots
- [ ] Verify test suite runs
- [ ] Document new patterns in test documentation

## Success Metrics

- **Zero Laravel imports**: No `use Illuminate\*` in active test files
- **Test passage rate**: At least 90% of tests pass
- **Execution time**: Test suite completes in <2 minutes
- **No backup files**: All `.backup`, `.bak`, `.new` files removed
