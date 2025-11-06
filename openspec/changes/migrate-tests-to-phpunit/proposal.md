# Change: Migrate Test Framework from Pest to PHPUnit

## Why

The project currently uses Pest as its testing framework, which was originally designed for Laravel. As part of the migration to Symfony, we need to transition to native PHPUnit to:

1. **Framework Alignment**: PHPUnit is the standard testing framework in the Symfony ecosystem, providing better integration with Symfony's testing tools and conventions.
2. **Simplify Dependencies**: Remove Laravel-specific testing dependencies (Pest, Spatie Snapshots) that are no longer needed in a Symfony-first codebase.
3. **Improve Maintainability**: Use industry-standard PHPUnit syntax that is more widely understood and supported in the PHP community.
4. **Enhance IDE Support**: Better autocomplete, navigation, and debugging support in modern IDEs for PHPUnit's class-based approach.

## What Changes

- Convert all Pest test files to PHPUnit test classes
  - Transform `it()` and `test()` functions to PHPUnit test methods
  - Convert `beforeEach()` to `setUp()` methods
  - Replace Pest `expect()` assertions with PHPUnit assertions
  - Remove `uses()` trait declarations and convert to class-based test structure
- Update test utilities and helpers
  - Convert `getTestSourceCode()` helper to work with PHPUnit reflection
  - Migrate custom Pest expectations to PHPUnit custom assertions
  - Update `analyzeFile()`, `analyzeClass()`, and other test helpers
- Update configuration files
  - **BREAKING**: Remove Pest configuration from `composer.json`
  - Update `phpunit.xml.dist` for PHPUnit-only execution
  - Remove Pest plugin references
- Update documentation
  - Update test examples in README and docs
  - Update CI/CD pipeline scripts to use PHPUnit

## Impact

- **Affected specs**: `testing` (creating new spec)
- **Affected code**: 
  - All test files in `tests/` directory (~50+ files)
  - `tests/Pest.php` → will be removed or converted to base test class
  - `tests/SymfonyTestCase.php` → may need updates for PHPUnit integration
  - `composer.json` → dependency changes
  - `.github/workflows/` → CI script updates (if exists)
- **Breaking Changes**: 
  - **BREAKING**: Test execution command changes from `vendor/bin/pest` to `vendor/bin/phpunit`
  - **BREAKING**: Pest plugin and dependencies will be removed
  - Custom Pest plugins (e.g., snapshots) need PHPUnit equivalents
- **Migration Path**: 
  - All tests will be converted in a single change
  - Snapshot tests may need manual verification after conversion
  - Test execution in CI/CD needs to be updated simultaneously

## Dependencies

This change builds upon the existing `migrate-to-symfony` change and should be completed as part of the Symfony migration effort.

## Risks

1. **Snapshot Testing**: Pest's snapshot plugin may have different behavior than PHPUnit alternatives
2. **Test Coverage**: Ensure all test patterns (data providers, expectations, setup/teardown) are correctly converted
3. **Hidden Dependencies**: Some tests may rely on Pest-specific behavior that's not immediately obvious

## Success Criteria

- All tests pass with PHPUnit
- No Pest dependencies remain in `composer.json`
- Test coverage remains at or above current levels
- CI/CD pipeline successfully runs tests with PHPUnit
- All custom assertions and helpers work correctly
