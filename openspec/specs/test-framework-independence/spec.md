# test-framework-independence Specification

## Purpose
TBD - created by archiving change eliminate-laravel-test-deps. Update Purpose after archive.
## Requirements
### Requirement: Tests SHALL NOT depend on Laravel Foundation framework classes

Tests SHALL NOT use Laravel's Foundation convenience traits or base classes, and SHALL use Symfony equivalents or direct implementations instead.

#### Scenario: Test controller does not use Foundation traits
- **GIVEN** a test controller class
- **WHEN** the controller is defined
- **THEN** it SHALL NOT use `Illuminate\Foundation\Auth\Access\AuthorizesRequests`
- **AND** it SHALL NOT use `Illuminate\Foundation\Bus\DispatchesJobs`
- **AND** it SHALL NOT use `Illuminate\Foundation\Validation\ValidatesRequests`
- **AND** it SHALL NOT extend `Illuminate\Routing\Controller`

#### Scenario: Authorization checks use Symfony Security
- **GIVEN** a test controller method requiring authorization
- **WHEN** the authorization check is performed
- **THEN** it SHALL use Symfony's `AuthorizationCheckerInterface`
- **OR** it SHALL throw `Symfony\Component\Security\Core\Exception\AccessDeniedException`
- **AND** it SHALL NOT use `$this->authorize()` from Laravel's AuthorizesRequests trait

### Requirement: Tests SHALL use Symfony Security exceptions instead of Laravel Auth exceptions

Authentication and authorization exceptions SHALL use Symfony's security component.

#### Scenario: Authentication exception uses Symfony
- **GIVEN** a test that throws an authentication exception
- **WHEN** the exception is created
- **THEN** it SHALL use `Symfony\Component\Security\Core\Exception\AuthenticationException`
- **AND** it SHALL NOT use `Illuminate\Auth\AuthenticationException`

#### Scenario: Authorization exception uses Symfony
- **GIVEN** a test that throws an authorization exception
- **WHEN** the exception is created
- **THEN** it SHALL use `Symfony\Component\Security\Core\Exception\AccessDeniedException`
- **AND** it SHALL NOT use Laravel's authorization exceptions

### Requirement: Tests SHALL NOT use Laravel Facades

Tests SHALL NOT rely on Laravel's Facade pattern, and SHALL use direct service injection or Symfony service container instead.

#### Scenario: Route definition does not use Route facade
- **GIVEN** a test needing to define routes
- **WHEN** routes are created
- **THEN** it SHALL use Symfony's `RouteCollection` and `Route` classes
- **AND** it SHALL NOT use `Illuminate\Support\Facades\Route`

#### Scenario: Schema operations do not use Schema facade
- **GIVEN** a test needing to set up database schema
- **WHEN** tables are created
- **THEN** it SHALL use Doctrine DBAL's `Schema` or direct SQL
- **AND** it SHALL NOT use `Illuminate\Support\Facades\Schema`

#### Scenario: Validation does not use Validator facade
- **GIVEN** a test that validates data
- **WHEN** validation is performed
- **THEN** it SHALL use Symfony's `Validator` service or direct validation
- **AND** it SHALL NOT use `Illuminate\Support\Facades\Validator`

### Requirement: Database schema setup SHALL use Doctrine DBAL or SQL fixtures

Test database schema creation SHALL use Symfony-compatible tools instead of Laravel migrations.

#### Scenario: Test schema is created with Doctrine DBAL
- **GIVEN** a test requiring database tables
- **WHEN** the test setup is executed
- **THEN** it SHALL use `Doctrine\DBAL\Schema\Schema` to define tables
- **OR** it SHALL use direct SQL DDL statements
- **AND** it SHALL NOT use `Illuminate\Database\Migrations\Migration`
- **AND** it SHALL NOT use `Illuminate\Database\Schema\Blueprint`

#### Scenario: Schema builder is framework-agnostic
- **GIVEN** a `TestSchemaBuilder` class
- **WHEN** tables are created
- **THEN** it SHALL accept a `Doctrine\DBAL\Connection`
- **AND** it SHALL use Doctrine's schema API
- **AND** it SHALL be reusable across different tests

### Requirement: Eloquent model test fixtures SHALL be isolated and documented

Laravel Eloquent models used as test fixtures SHALL be clearly identified as intentional Laravel compatibility testing artifacts.

#### Scenario: Eloquent models are in Fixtures directory
- **GIVEN** an Eloquent model used in tests
- **WHEN** the file location is checked
- **THEN** it SHALL be located in `tests/Fixtures/Laravel/Models/`
- **AND** it SHALL NOT be in the root `tests/Files/` directory
- **AND** the directory SHALL contain a README explaining the intentional Laravel dependency

#### Scenario: Fixture README documents intentional dependencies
- **GIVEN** the Laravel fixtures directory
- **WHEN** the README.md is read
- **THEN** it SHALL explain that Laravel dependencies are intentional
- **AND** it SHALL list which Laravel classes are deliberately used
- **AND** it SHALL explain why these fixtures are necessary (e.g., "testing Laravel API resource documentation")

### Requirement: Tests SHALL use abstraction interfaces for validation rule extraction

Validation rule extraction SHALL work through interfaces to support multiple frameworks.

#### Scenario: Validation rule extractor uses interface
- **GIVEN** a test extracting validation rules
- **WHEN** the extractor is used
- **THEN** it SHALL depend on `ValidationRuleExtractorInterface`
- **AND** it SHALL NOT directly instantiate Laravel-specific extractors
- **AND** the interface SHALL be injected via dependency injection

#### Scenario: Laravel validation adapter implements interface
- **GIVEN** a `LaravelValidationRuleExtractor` class
- **WHEN** the class is defined
- **THEN** it SHALL implement `ValidationRuleExtractorInterface`
- **AND** it SHALL contain all Laravel-specific logic
- **AND** it SHALL be registered in the service container with the interface binding

### Requirement: Tests SHALL use abstraction interfaces for pagination type handling

Pagination type detection and schema generation SHALL work through interfaces to support multiple frameworks.

#### Scenario: Pagination type uses interface
- **GIVEN** a test working with paginated responses
- **WHEN** the pagination type is determined
- **THEN** it SHALL use `PaginationTypeInterface`
- **AND** it SHALL NOT directly reference `Illuminate\Pagination\Paginator`
- **AND** multiple pagination type implementations SHALL be supported

#### Scenario: Laravel pagination adapter implements interface
- **GIVEN** a `LaravelPaginatorType` class
- **WHEN** the class is defined
- **THEN** it SHALL implement `PaginationTypeInterface`
- **AND** it SHALL handle Laravel's Paginator, LengthAwarePaginator, and CursorPaginator
- **AND** it SHALL be registered as one of multiple pagination type providers

