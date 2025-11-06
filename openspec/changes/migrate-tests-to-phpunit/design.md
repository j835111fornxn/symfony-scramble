# Design: Migrate Test Framework from Pest to PHPUnit

## Context

Scramble is transitioning from a Laravel package to a Symfony bundle. The test suite currently uses Pest, a Laravel-oriented testing framework that provides a functional testing API on top of PHPUnit. As part of the Symfony migration, we need to adopt PHPUnit's native class-based approach, which is the standard in the Symfony ecosystem.

**Current State:**
- ~50+ test files using Pest's functional API (`it()`, `test()`, `expect()`)
- Custom Pest expectations (`toBeSameJson`, `toHaveType`)
- Pest setup file (`tests/Pest.php`) with helper functions and global configuration
- Snapshot testing using Spatie's Pest plugin
- Mix of unit, integration, and feature tests

**Constraints:**
- Must maintain 100% test coverage
- Cannot break existing test functionality
- Should minimize disruption to development workflow
- Must preserve snapshot test behavior

## Goals / Non-Goals

**Goals:**
- Convert all Pest tests to PHPUnit native syntax
- Maintain or improve test readability and maintainability
- Remove all Laravel-specific testing dependencies
- Preserve all test functionality and coverage
- Ensure CI/CD compatibility

**Non-Goals:**
- Rewriting test logic or changing test coverage
- Optimizing test performance (can be done separately)
- Adding new test cases (separate from migration)
- Changing test organization or structure

## Decisions

### Decision 1: Conversion Strategy - Big Bang vs Incremental

**Chosen: Big Bang (single PR)**

**Rationale:**
- Pest and PHPUnit cannot coexist cleanly in test execution
- Running mixed tests would require complex configuration
- Clean git history with a single atomic change
- Easier code review as all patterns are visible together

**Alternatives Considered:**
- Incremental (directory-by-directory): Would require maintaining both frameworks simultaneously, complex configuration, and multiple PRs
- Keep Pest: Not viable as it's Laravel-centric and not idiomatic in Symfony ecosystem

### Decision 2: Base Test Class Structure

**Chosen: Keep `SymfonyTestCase` as base, move helpers to traits**

**Rationale:**
- `SymfonyTestCase` already provides Symfony integration
- Trait-based helpers allow selective inclusion
- Clear separation of concerns

**Structure:**
```php
abstract class SymfonyTestCase extends TestCase
{
    use CreatesApplication;
    
    protected static function bootKernel(): KernelInterface { ... }
    protected static function getContainer(): ContainerInterface { ... }
}

trait TypeInferenceAssertions
{
    protected function assertTypeEquals(string $expected, Type $actual) { ... }
    protected function assertSameJson(mixed $expected, mixed $actual) { ... }
}

trait AnalysisHelpers
{
    protected function analyzeFile(string $code): AnalysisResult { ... }
    protected function analyzeClass(string $className): AnalysisResult { ... }
}
```

### Decision 3: Custom Assertion Conversion

**Pest Custom Expectation → PHPUnit Custom Assertion**

| Pest | PHPUnit Approach |
|------|------------------|
| `expect($x)->toBeSameJson($y)` | `$this->assertSameJson($x, $y)` |
| `expect($x)->toHaveType($type)` | `$this->assertTypeEquals($type, $x)` |

**Rationale:**
- PHPUnit custom assertions are class methods, not extensions
- More explicit and discoverable in IDEs
- Better type safety and autocomplete

### Decision 4: Snapshot Testing

**Chosen: Use PHPUnit snapshot assertion library or inline snapshots**

**Options:**
1. Use `spatie/phpunit-snapshot-assertions` (PHPUnit version of their Pest plugin)
2. Inline snapshots in test files (for small snapshots)
3. Manual file-based snapshots

**Rationale:**
- Spatie provides PHPUnit version, minimal migration needed
- Snapshot format should remain compatible
- Can be evaluated during implementation

### Decision 5: Test Method Naming

**Chosen: Use PHPUnit conventions with descriptive names**

```php
// Pest
it('supports present rule', function () { ... });

// PHPUnit
public function testSupportsPresentRule(): void { ... }
// or with attribute
#[Test]
public function supportsPresentRule(): void { ... }
```

**Rationale:**
- Follow Symfony/PHPUnit conventions
- Better IDE support and navigation
- Clear test intent from method name

### Decision 6: Data Providers

**Pest:**
```php
it('transforms simple types', function ($type, $openApiArrayed) {
    // ...
})->with('simpleTypes');
```

**PHPUnit:**
```php
#[DataProvider('simpleTypesProvider')]
public function testTransformsSimpleTypes($type, $openApiArrayed): void {
    // ...
}

public static function simpleTypesProvider(): array {
    return [ /* ... */ ];
}
```

**Rationale:**
- PHPUnit data providers are static methods
- More explicit and type-safe
- Better for complex data scenarios

## Implementation Pattern

### Step-by-Step Conversion Process

**For each test file:**

1. **Class Declaration**
   ```php
   // Before (Pest)
   uses(SymfonyTestCase::class)->in(__DIR__);
   
   // After (PHPUnit)
   final class ValidationRulesDocumentingTest extends SymfonyTestCase
   {
   ```

2. **Setup/Teardown**
   ```php
   // Before
   beforeEach(function () {
       $this->transformer = new TypeToSchemaTransformer();
   });
   
   // After
   protected function setUp(): void
   {
       parent::setUp();
       $this->transformer = new TypeToSchemaTransformer();
   }
   ```

3. **Test Methods**
   ```php
   // Before
   it('supports present rule', function () {
       $result = analyzeFile(__DIR__.'/Stubs/ValidationRulesStub.php');
       expect($result)->toBeSomeValue();
   });
   
   // After
   public function testSupportsPresentRule(): void
   {
       $result = $this->analyzeFile(__DIR__.'/Stubs/ValidationRulesStub.php');
       $this->assertSomeValue($result);
   }
   ```

4. **Assertions Mapping**
   ```php
   // Pest → PHPUnit
   expect($x)->toBe($y)           → $this->assertSame($y, $x)
   expect($x)->toEqual($y)        → $this->assertEquals($y, $x)
   expect($x)->toBeTrue()         → $this->assertTrue($x)
   expect($x)->toBeFalse()        → $this->assertFalse($x)
   expect($x)->toBeNull()         → $this->assertNull($x)
   expect($x)->toBeArray()        → $this->assertIsArray($x)
   expect($x)->toBeString()       → $this->assertIsString($x)
   expect($x)->toContain($y)      → $this->assertContains($y, $x)
   expect($x)->toHaveCount($n)    → $this->assertCount($n, $x)
   ```

## Risks / Trade-offs

### Risk 1: Test Behavior Differences
- **Risk**: Subtle differences between Pest and PHPUnit execution may cause tests to behave differently
- **Mitigation**: Run comprehensive test suite comparison before/after, check coverage reports
- **Impact**: Medium

### Risk 2: Snapshot Test Compatibility
- **Risk**: Snapshot format or generation may differ between Pest and PHPUnit plugins
- **Mitigation**: Manual verification of snapshot tests, regenerate if needed
- **Impact**: Low to Medium

### Risk 3: Custom Expectation Logic Errors
- **Risk**: Custom `toHaveType` expectation has complex logic that might break during conversion
- **Mitigation**: Add dedicated unit tests for custom assertions, thorough manual testing
- **Impact**: Medium

### Risk 4: CI/CD Pipeline Issues
- **Risk**: CI/CD configuration may need updates that aren't immediately obvious
- **Mitigation**: Test CI/CD changes in separate branch first
- **Impact**: Low

### Trade-off: Verbosity vs Explicitness
- Pest's functional API is more concise
- PHPUnit's class-based approach is more verbose but more explicit
- **Decision**: Accept increased verbosity for better IDE support and Symfony ecosystem alignment

## Migration Plan

### Phase 1: Infrastructure (Week 1)
1. Update base test class and traits
2. Convert test helpers and utilities
3. Implement custom assertions
4. Test infrastructure changes in isolation

### Phase 2: Test Conversion (Week 1-2)
1. Convert core inference tests (highest complexity)
2. Convert feature tests (medium complexity)
3. Convert component tests (lower complexity)
4. Convert extension tests (lower complexity)

### Phase 3: Verification (Week 2)
1. Run full test suite
2. Compare coverage reports
3. Verify snapshot tests
4. Update CI/CD

### Phase 4: Cleanup (Week 2)
1. Remove Pest dependencies
2. Update documentation
3. Final validation

### Rollback Plan
If critical issues are discovered:
1. Revert the migration commit
2. Fix identified issues in separate branch
3. Re-attempt migration

## Open Questions

1. **Q**: Should we use PHPUnit 10 or 11?
   - **A**: Use PHPUnit 11 (already in composer.json as `^11.5.3`), it's the latest stable

2. **Q**: How to handle test that rely on Pest's `$this` binding in closures?
   - **A**: Convert to instance variables in test class, access via `$this` in methods

3. **Q**: Should we keep `dataset()` definitions or inline them?
   - **A**: Convert to static data provider methods for better type safety

4. **Q**: What about tests that use Pest's `arch()` testing?
   - **A**: None found in current codebase, not applicable

## References

- [PHPUnit Documentation](https://docs.phpunit.de/en/11.5/)
- [Symfony Testing Guide](https://symfony.com/doc/current/testing.html)
- [PHPUnit Migration Guide](https://docs.phpunit.de/en/11.5/installation.html)
- [Spatie PHPUnit Snapshot Assertions](https://github.com/spatie/phpunit-snapshot-assertions)
