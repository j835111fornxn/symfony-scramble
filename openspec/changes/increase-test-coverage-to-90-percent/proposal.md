# Proposal: Increase Test Coverage to 90%

## Overview
This change aims to significantly improve test coverage for the Scramble library by achieving at least 90% code coverage. Current test coverage is estimated to be below 50% based on the ratio of source files to test files and the number of untested directories.

## Problem Statement
The project currently has:
- 354 source files with ~27,668 lines of code
- Only 60 test files with ~8,013 lines of test code
- 11 major source directories with no corresponding test coverage:
  - Attributes (configuration attributes)
  - Configuration (system configuration)
  - Contracts (interfaces)
  - DependencyInjection (Symfony DI integration)
  - DocumentTransformers (OpenAPI document transformers)
  - Event (event objects)
  - Exceptions (exception classes)
  - Extensions (extensibility points)
  - Http (HTTP controllers)
  - OpenApiVisitor (OpenAPI traversal)
  - Reflection (route reflection)
- 141 source files in untested directories (40% of total codebase)

Additionally, the test suite is currently incomplete due to:
- 4 test files still using Pest syntax that need migration to PHPUnit
- Some missing test stubs from the Laravel to Symfony migration

## Goals
1. **Achieve 90% test coverage** across the entire codebase
2. **Complete test migration** by converting remaining Pest tests to PHPUnit
3. **Add comprehensive tests** for all untested directories
4. **Establish testing standards** to maintain coverage going forward

## Scope
### In Scope
- Complete migration of 4 remaining Pest test files to PHPUnit
- Add unit tests for all critical classes in untested directories
- Add integration tests for key workflows (documentation generation, validation, etc.)
- Configure PHPUnit coverage reporting
- Document testing guidelines and patterns

### Out of Scope
- Performance testing
- End-to-end browser testing
- Load/stress testing
- Mutation testing (may be added in future)

## Success Criteria
1. PHPUnit coverage report shows ≥90% line coverage
2. All 4 remaining Pest test files are converted to PHPUnit
3. Each major source directory has corresponding test directory with tests
4. All tests pass successfully
5. Testing documentation is updated

## Affected Specifications
- `testing` - Will add new requirements for coverage standards
- `test-framework-independence` - May need updates for coverage tooling

## Dependencies
- Existing testing infrastructure must be functional
- PHPUnit and coverage tools must be properly configured
- PHP extensions (xdebug or pcov) for coverage collection

## Risks and Mitigations
| Risk | Impact | Mitigation |
|------|--------|------------|
| Writing tests may reveal bugs | Medium | Document and fix bugs as they are discovered |
| Coverage tooling setup issues | Low | Use PHPUnit's built-in coverage with xdebug/pcov |
| Time-consuming for large codebase | High | Prioritize critical paths and high-value components first |
| False sense of security from metrics | Medium | Focus on meaningful tests, not just coverage numbers |

## Implementation Phases
### Phase 1: Foundation (Week 1)
- Complete Pest to PHPUnit migration (4 files)
- Configure coverage reporting in phpunit.xml
- Establish baseline coverage metrics

### Phase 2: Critical Components (Week 2-3)
- Add tests for Generator and core OpenAPI generation
- Test DependencyInjection and service integration
- Test DocumentTransformers and extensions system

### Phase 3: Supporting Components (Week 4-5)
- Add tests for Attributes and Configuration
- Test EventSubscriber and Event classes
- Test Http controllers and routing reflection
- Test Exceptions and error handling

### Phase 4: Complete Coverage (Week 6)
- Fill remaining gaps to reach 90%
- Add integration tests for key workflows
- Document testing patterns and guidelines

## Alternatives Considered
1. **Target 100% coverage** - Rejected: Diminishing returns, some code may not need tests (simple DTOs, etc.)
2. **Target 80% coverage** - Rejected: Too low for a library that generates API documentation
3. **Phased approach with 70% then 90%** - Rejected: Better to target final goal directly

## Open Questions
1. Should we include tests for deprecated classes?
2. What coverage metric should we prioritize (line, branch, or path)?
3. Should Contracts (interfaces) count toward coverage requirements?

## Related Changes
None currently.
