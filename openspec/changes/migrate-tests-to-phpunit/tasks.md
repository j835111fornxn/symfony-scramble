# Implementation Tasks

## 1. Preparation and Analysis
- [ ] 1.1 Audit all test files to identify Pest-specific patterns
- [ ] 1.2 Create inventory of custom expectations and assertions
- [ ] 1.3 Document snapshot test locations and patterns
- [ ] 1.4 Review test helper functions in `tests/Pest.php`

## 2. Update Test Infrastructure
- [x] 2.1 Create base PHPUnit test class (if converting `tests/Pest.php`)
- [x] 2.2 Convert custom Pest expectations to PHPUnit custom assertions
  - [x] 2.2.1 Convert `toBeSameJson` expectation
  - [x] 2.2.2 Convert `toHaveType` expectation
- [x] 2.3 Update test helper functions
  - [x] 2.3.1 Update `getTestSourceCode()` for PHPUnit
  - [x] 2.3.2 Update `analyzeFile()` helper
  - [x] 2.3.3 Update `analyzeClass()` helper
  - [x] 2.3.4 Update `generateForRoute()` helper
- [x] 2.4 Ensure `SymfonyTestCase` is PHPUnit-compatible

## 3. Convert Test Files (Batch 1: Core Tests)
- [x] 3.1 Convert `tests/InferTypesTest.php`
- [x] 3.2 Convert `tests/ComplexInferTypesTest.php`
- [ ] 3.3 Convert `tests/TypeToSchemaTransformerTest.php`
- [ ] 3.4 Convert `tests/OpenApiTraverserTest.php`
- [ ] 3.5 Convert `tests/OpenApiBuildersTest.php`

## 4. Convert Test Files (Batch 2: Feature Tests)
- [ ] 4.1 Convert `tests/ValidationRulesDocumentingTest.php`
- [ ] 4.2 Convert `tests/ResponseDocumentingTest.php`
- [ ] 4.3 Convert `tests/ResponsesInferTypesTest.php`
- [ ] 4.4 Convert `tests/ErrorsResponsesTest.php`
- [ ] 4.5 Convert `tests/ParametersSerializationTest.php`
- [ ] 4.6 Convert `tests/ResourceCollectionResponseTest.php`

## 5. Convert Test Files (Batch 3: Component Tests)
- [ ] 5.1 Convert all tests in `tests/Attributes/`
- [ ] 5.2 Convert all tests in `tests/Console/`
- [ ] 5.3 Convert all tests in `tests/DocumentTransformers/`
- [ ] 5.4 Convert all tests in `tests/EventSubscriber/`
- [ ] 5.5 Convert all tests in `tests/Generator/`

## 6. Convert Test Files (Batch 4: Extension Tests)
- [ ] 6.1 Convert all tests in `tests/Infer/`
- [ ] 6.2 Convert all tests in `tests/InferExtensions/`
- [ ] 6.3 Convert all tests in `tests/PhpDoc/`
- [ ] 6.4 Convert all tests in `tests/Reflection/`
- [ ] 6.5 Convert all tests in `tests/Support/`

## 7. Handle Snapshot Tests
- [ ] 7.1 Research PHPUnit snapshot testing alternatives
- [ ] 7.2 Convert or replace Spatie snapshot tests
- [ ] 7.3 Verify snapshot test behavior matches Pest implementation
- [ ] 7.4 Update `tests/__snapshots__/` if needed

## 8. Update Configuration
- [x] 8.1 Remove Pest from `composer.json` dependencies
- [x] 8.2 Remove Pest plugin configuration from `composer.json`
- [x] 8.3 Update `phpunit.xml.dist` configuration
- [x] 8.4 Update test scripts in `composer.json`
  - [x] 8.4.1 Change `test` script to use `phpunit`
  - [x] 8.4.2 Update `test-coverage` script
- [x] 8.5 Remove `tests/Pest.php` if fully converted to base class

## 9. Validation and Testing
- [ ] 9.1 Run all tests with PHPUnit and verify they pass
- [ ] 9.2 Compare test coverage reports (before vs after)
- [ ] 9.3 Verify snapshot tests produce identical results
- [ ] 9.4 Test all custom assertions work correctly
- [ ] 9.5 Run PHPStan analysis to catch any type issues

## 10. CI/CD and Documentation Updates
- [ ] 10.1 Update GitHub Actions workflow (if exists)
- [ ] 10.2 Update any local development scripts
- [ ] 10.3 Update README.md test instructions
- [ ] 10.4 Update MIGRATION.md with testing changes
- [ ] 10.5 Add migration notes to CHANGELOG.md

## 11. Cleanup
- [ ] 11.1 Remove Laravel test stubs that are no longer needed
- [ ] 11.2 Remove any remaining Pest-specific files
- [ ] 11.3 Clean up unused imports and dependencies
- [ ] 11.4 Run `composer update` to clean lock file

## Validation Checklist
After all tasks:
- [ ] All tests pass: `vendor/bin/phpunit`
- [ ] Coverage report generates: `vendor/bin/phpunit --coverage-html build/coverage`
- [ ] No Pest dependencies in `composer.lock`
- [ ] CI/CD pipeline passes (if applicable)
- [ ] `openspec validate migrate-tests-to-phpunit --strict` passes
