# Change: Eliminate Laravel Dependencies from Tests

## Why

Following the migration to Symfony and the conversion from Pest to PHPUnit, the test suite still contains significant Laravel dependencies that need to be eliminated or made framework-agnostic:

1. **Framework Independence**: Tests should use Symfony components or framework-agnostic approaches to align with the Symfony-first architecture.
2. **Reduce Maintenance Burden**: Eliminate unnecessary dependencies on Laravel packages that complicate dependency management and updates.
3. **Improve Test Clarity**: Make tests more focused on business logic rather than framework-specific implementation details.
4. **Enable Future Flexibility**: By abstracting framework-specific concerns, the codebase becomes more adaptable to future changes.

## What Changes

### Phase 1: Replace Foundation & Auth Dependencies (Low Effort)
- Remove Laravel Foundation traits from test controllers:
  - `Illuminate\Foundation\Auth\Access\AuthorizesRequests`
  - `Illuminate\Foundation\Bus\DispatchesJobs`
  - `Illuminate\Foundation\Validation\ValidatesRequests`
- Replace Laravel auth exceptions with Symfony equivalents:
  - `Illuminate\Auth\AuthenticationException` → `Symfony\Component\Security\Core\Exception\AuthenticationException`
  - `Illuminate\Auth\Middleware\Authorize` → Symfony security voters
  - `Illuminate\Support\Facades\Gate` → Symfony security authorization checker

### Phase 2: Migrate Database/Migration Dependencies (Medium Effort)
- Convert test migrations from Laravel to Doctrine DBAL or SQL fixtures:
  - Replace `Illuminate\Database\Migrations\Migration`
  - Replace `Illuminate\Database\Schema\Blueprint`
  - Replace `Illuminate\Support\Facades\Schema`
- Keep Eloquent model test fixtures for now (needed for API documentation testing)
  - Add clear documentation that these are intentional test fixtures for Laravel compatibility testing
  - Isolate them in a dedicated test fixtures directory

### Phase 3: Abstract Core Laravel Features (Long-term)
- Create abstraction layer for validation rule extraction to support both Laravel and Symfony validation
- Create abstraction layer for pagination responses to support multiple frameworks
- Document which Laravel classes are intentionally kept for integration testing vs. which should be replaced

## Impact

- **Affected specs**: `testing` (modifications to existing spec)
- **Affected code**:
  - ~40 test files with Laravel dependencies
  - Test controllers in test files using Foundation traits
  - Test migrations in `tests/migrations/`
  - Sample model files in `tests/Files/`
  - Exception handling tests in `tests/ErrorsResponsesTest.php`
- **Breaking Changes**:
  - **INTERNAL**: Test migration structure changes (no external API impact)
  - Some test helper methods may change signatures
- **Migration Path**:
  - Phase 1 can be completed immediately (low risk)
  - Phase 2 requires careful migration of test data fixtures
  - Phase 3 is a long-term architectural improvement

## Dependencies

This change builds upon:
- **migrate-tests-to-phpunit**: All tests must be converted to PHPUnit first
- Requires Symfony testing infrastructure to be fully functional

## Risks

1. **JsonResource/Pagination Testing**: These are core to Scramble's functionality. Replacing them requires careful abstraction design.
2. **Eloquent Model Fixtures**: Models are used extensively to test API documentation generation. They may need to remain as-is for compatibility testing.
3. **Validation Rules**: Laravel validation rule extraction is a key feature. Abstraction must not break existing functionality.
4. **Test Data Setup**: Migrations provide test data. Alternative fixture loading must be equally reliable.

## Success Criteria

- Phase 1:
  - All Foundation traits removed from test controllers
  - All Auth-related exceptions use Symfony equivalents
  - Tests pass without changes to production code
- Phase 2:
  - Test migrations converted to Doctrine migrations or SQL fixtures
  - Database schema setup works reliably in tests
  - Eloquent model fixtures are clearly documented and isolated
- Phase 3:
  - Validation rule abstraction supports both frameworks
  - Pagination abstraction supports both frameworks
  - Clear documentation of intentional Laravel compatibility testing

## Out of Scope

- **Removing all Laravel dependencies**: Some Laravel-specific testing is intentional (e.g., testing Laravel API resource documentation generation)
- **Production code changes**: This change only affects test code
- **Removing JsonResource/Eloquent entirely**: These may be kept as test fixtures for compatibility testing
