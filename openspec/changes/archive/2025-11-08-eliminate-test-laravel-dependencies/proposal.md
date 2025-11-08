# Proposal: Eliminate Test Laravel Dependencies

## Overview
This change removes all remaining Laravel dependencies from the test suite, replacing them with Symfony equivalents or framework-agnostic implementations. Currently, tests reference Laravel Models, Collections, and JsonResource classes that cause test failures and maintain unnecessary coupling to the Laravel framework.

## Problem Statement
After migrating the main codebase from Laravel to Symfony, the test suite still contains references to Laravel-specific classes and incomplete migration artifacts:

1. **Laravel Model References** (3 files):
   - `tests/InferTypesTest.php` references `Fixtures\Laravel\Models\SamplePostModel`, `SamplePostModelWithToArray`, `SampleUserModel`
   - These models don't exist and cause test failures

2. **JsonResource Stub Issues** (1 file):
   - `tests/Stubs/JsonResource.php` is a minimal stub created during migration
   - Tests using JsonResource fail because the stub lacks full functionality

3. **Backup Files** (4 files):
   - `tests/TestCase.php.laravel-backup`
   - `tests/TypeToSchemaTransformerTest.php.bak`
   - `tests/TypeToSchemaTransformerTest.php.new`
   - `tests/Pest.php.backup`
   - These are leftover files from the Laravel→Symfony and Pest→PHPUnit migrations

4. **Test Snapshot References**:
   - Multiple snapshot files reference Laravel/Illuminate classes in expected output
   - Snapshots need to be regenerated to reflect Symfony-based types

5. **Incomplete Pest Migration**:
   - While Pest dependencies have been removed from composer.json
   - 4 test files were migrated to PHPUnit in previous commit
   - Backup files remain from the migration process

## Goals
1. **Remove Laravel Model dependencies** - Replace with Doctrine entities or test doubles
2. **Replace JsonResource** - Use Symfony serialization or remove JsonResource-specific tests
3. **Clean up migration artifacts** - Remove all `.backup`, `.bak`, `.new` files from both Laravel and Pest migrations
4. **Complete Pest→PHPUnit migration cleanup** - Remove Pest backup files and verify no Pest syntax remains
5. **Update test snapshots** - Regenerate snapshots to reflect Symfony-based output
6. **Ensure all tests pass** - Fix the 342 errors currently preventing test execution

## Scope
### In Scope
- Replace Laravel Model references with Doctrine entities in `tests/InferTypesTest.php`
- Remove or rewrite JsonResource-dependent tests
- Delete all backup files from Laravel→Symfony migration (`.laravel-backup`)
- Delete all backup files from Pest→PHPUnit migration (`.backup`, `.bak`, `.new`)
- Verify no Pest syntax remains in active test files
- Update test expectations and snapshots to reflect Symfony types
- Fix related test failures caused by Laravel dependencies

### Out of Scope
- Adding new test cases (covered by `increase-test-coverage-to-90-percent`)
- Performance optimization of tests
- Refactoring test structure beyond dependency removal
- Migrating additional Pest tests (already completed in previous commit)

## Success Criteria
1. No `use` statements importing from `Illuminate\*` or Laravel packages in active test files
2. All migration backup files are removed:
   - No `.laravel-backup` files
   - No `.backup` files (Pest migration)
   - No `.bak` or `.new` files
3. No Pest syntax (`test()`, `expect()`) in active test files
4. Test suite executes without Laravel-related errors
5. Test errors reduced from 342 to <50 (some may remain from other issues)
6. At least 90% of tests that were passing before Laravel migration now pass
7. All test snapshots reference Symfony/Doctrine types, not Laravel/Illuminate

## Affected Specifications
- `test-framework-independence` - MODIFIED: Add requirements for model and data fixture independence
- `testing` - MODIFIED: Update to reflect Doctrine-based test fixtures

## Dependencies
- Doctrine ORM must be properly configured (completed in previous commit)
- Symfony test infrastructure must be functional (completed in previous commit)
- `SymfonyTestCase` base class must be available (exists)

## Risks and Mitigations
| Risk | Impact | Mitigation |
|------|--------|------------|
| Tests become harder to understand | Medium | Document test fixture patterns clearly |
| Snapshot regeneration breaks tests | High | Regenerate snapshots carefully and verify manually |
| Unknown Laravel dependencies discovered | Medium | Use comprehensive grep searches before starting |
| Time-consuming to rewrite tests | Medium | Prioritize high-value tests, mark others as skipped temporarily |

## Implementation Phases
### Phase 1: Analysis and Planning
- Catalog all Laravel dependencies in tests
- Identify which tests are critical vs. can be temporarily skipped
- Design Doctrine entity fixtures to replace Laravel models

### Phase 2: Model Replacement
- Create Doctrine entity equivalents for `SamplePostModel`, `SampleUserModel`
- Update `InferTypesTest.php` to use Doctrine entities
- Update related tests

### Phase 3: JsonResource Handling
- Evaluate if JsonResource tests are still needed
- Either: Remove JsonResource tests entirely, OR
- Implement proper Symfony serialization equivalent

### Phase 4: Cleanup
- Delete all backup files
- Regenerate test snapshots
- Run full test suite and fix remaining issues

## Alternatives Considered
1. **Keep Laravel as test-only dependency** - Rejected: Defeats purpose of framework independence
2. **Skip all failing tests** - Rejected: Reduces confidence in migration
3. **Rewrite all tests from scratch** - Rejected: Too time-consuming, loses existing coverage

## Open Questions
1. Should we create full Doctrine entities or use simple test doubles for models?
2. Are JsonResource-specific tests still valuable after Symfony migration?
3. Should we regenerate all snapshots or only those that fail?

## Related Changes
- Depends on: Completed Symfony migration
- Blocks: `increase-test-coverage-to-90-percent` (Phase 2+)
- Related: Previous commits fixing container configuration
