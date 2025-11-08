# testing Specification

## Purpose
TBD - created by archiving change migrate-tests-to-phpunit. Update Purpose after archive.
## Requirements
### Requirement: PHPUnit as Primary Testing Framework
The testing infrastructure SHALL use PHPUnit as the primary and only testing framework for all automated tests.

#### Scenario: Test execution with PHPUnit
- **GIVEN** a developer wants to run the test suite
- **WHEN** they execute `vendor/bin/phpunit`
- **THEN** all tests SHALL execute using PHPUnit
- **AND** no Pest dependencies SHALL be required

#### Scenario: Test file structure
- **GIVEN** a test file in the test suite
- **WHEN** the file is parsed
- **THEN** it SHALL be a valid PHPUnit test class
- **AND** it SHALL extend a base test case class
- **AND** it SHALL follow PHPUnit naming conventions

### Requirement: Class-Based Test Structure
All test files SHALL use PHPUnit's class-based test structure with explicit test methods.

#### Scenario: Test class definition
- **GIVEN** a test file
- **WHEN** defining tests
- **THEN** tests SHALL be defined as public methods in a test class
- **AND** test methods SHALL be prefixed with `test` or use the `#[Test]` attribute
- **AND** test methods SHALL have a `void` return type declaration

#### Scenario: Test class inheritance
- **GIVEN** a test class
- **WHEN** the class is defined
- **THEN** it SHALL extend `SymfonyTestCase` or `PHPUnit\Framework\TestCase`
- **AND** it SHALL be marked as `final` unless designed for extension

### Requirement: PHPUnit Assertions
Test assertions SHALL use PHPUnit's native assertion methods.

#### Scenario: Basic assertions
- **GIVEN** a test method
- **WHEN** making assertions
- **THEN** assertions SHALL use PHPUnit methods like `assertSame()`, `assertEquals()`, `assertTrue()`
- **AND** assertions SHALL NOT use Pest's `expect()` syntax

#### Scenario: Custom assertions
- **GIVEN** a need for domain-specific assertions
- **WHEN** implementing custom assertions
- **THEN** they SHALL be implemented as protected methods in test classes or traits
- **AND** they SHALL follow PHPUnit's assertion naming pattern (`assert*()`)

### Requirement: Base Test Case Integration
The framework SHALL provide a base test case class that integrates with Symfony's testing infrastructure.

#### Scenario: Symfony test case usage
- **GIVEN** a test requiring Symfony integration
- **WHEN** the test class is defined
- **THEN** it SHALL extend `SymfonyTestCase`
- **AND** the test SHALL have access to the service container
- **AND** the test SHALL be able to boot the Symfony kernel

#### Scenario: Kernel and container access
- **GIVEN** a test extending `SymfonyTestCase`
- **WHEN** the test needs to access services
- **THEN** it SHALL use `static::getContainer()` to retrieve the service container
- **AND** it SHALL use `static::bootKernel()` to initialize the kernel if needed

### Requirement: Test Setup and Teardown
Tests SHALL use PHPUnit's lifecycle methods for setup and teardown operations.

#### Scenario: Per-test setup
- **GIVEN** a test class requiring initialization before each test
- **WHEN** defining the setup logic
- **THEN** it SHALL implement a `setUp()` method
- **AND** the method SHALL call `parent::setUp()` first
- **AND** the method SHALL be marked as `protected`

#### Scenario: Per-test teardown
- **GIVEN** a test class requiring cleanup after each test
- **WHEN** defining the teardown logic
- **THEN** it SHALL implement a `tearDown()` method
- **AND** the method SHALL call `parent::tearDown()` last
- **AND** the method SHALL be marked as `protected`

### Requirement: Data Providers
Tests requiring multiple input scenarios SHALL use PHPUnit data providers.

#### Scenario: Data provider definition
- **GIVEN** a test method that needs to run with multiple data sets
- **WHEN** defining the data provider
- **THEN** it SHALL be implemented as a `public static` method
- **AND** it SHALL return an `array` of test cases
- **AND** it SHALL be referenced using the `#[DataProvider('methodName')]` attribute

#### Scenario: Data provider usage
- **GIVEN** a test method with a data provider
- **WHEN** the test executes
- **THEN** PHPUnit SHALL execute the test once for each data set
- **AND** each execution SHALL receive the corresponding data set as method parameters

### Requirement: Test Helper Functions
Common test utilities SHALL be provided through helper methods and traits.

#### Scenario: Analysis helpers
- **GIVEN** a test needing to analyze PHP code
- **WHEN** the test uses analysis helpers
- **THEN** it SHALL access `analyzeFile()` and `analyzeClass()` methods
- **AND** these methods SHALL return `AnalysisResult` instances
- **AND** these methods SHALL integrate with the Symfony service container

#### Scenario: Route generation helper
- **GIVEN** a test needing to generate OpenAPI documentation for a route
- **WHEN** using the route generation helper
- **THEN** it SHALL use `generateForRoute()` method
- **AND** the method SHALL accept a Symfony `Route` or a callable
- **AND** the method SHALL use the Scramble generator from the container

#### Scenario: Type inference assertions
- **GIVEN** a test verifying inferred types
- **WHEN** making type assertions
- **THEN** it SHALL use custom assertion methods like `assertTypeEquals()`
- **AND** it SHALL access these through traits or base test class methods

### Requirement: JSON Comparison Assertions
Tests SHALL provide specialized assertions for comparing JSON structures.

#### Scenario: JSON equality assertion
- **GIVEN** a test comparing JSON data
- **WHEN** using the JSON assertion
- **THEN** it SHALL use `assertSameJson($expected, $actual)` method
- **AND** the method SHALL normalize JSON before comparison
- **AND** the method SHALL use pretty-print formatting for readable diffs

### Requirement: Type Inference Assertions
Tests SHALL provide specialized assertions for verifying inferred PHP types.

#### Scenario: Type string comparison
- **GIVEN** a test verifying an inferred type
- **WHEN** asserting the type
- **THEN** it SHALL use `assertTypeEquals($expectedType, $actualType)` method
- **AND** the method SHALL compare type string representations
- **OR** it SHALL accept a callable for custom type validation

#### Scenario: Expression type inference
- **GIVEN** a PHP code statement
- **WHEN** inferring its type
- **THEN** the test SHALL parse and analyze the code
- **AND** it SHALL resolve references through the type resolver
- **AND** it SHALL return the complete inferred type

### Requirement: Snapshot Testing Support
The framework SHALL support snapshot testing for comparing complex output structures.

#### Scenario: Snapshot assertion
- **GIVEN** a test with complex output (e.g., OpenAPI schema)
- **WHEN** using snapshot testing
- **THEN** it SHALL use a PHPUnit-compatible snapshot assertion library
- **AND** it SHALL store snapshots in `tests/__snapshots__/` directory
- **AND** it SHALL support snapshot regeneration when intentional changes occur

### Requirement: Test Configuration
Test execution SHALL be configured through `phpunit.xml.dist`.

#### Scenario: PHPUnit configuration file
- **GIVEN** the project root directory
- **WHEN** PHPUnit is executed
- **THEN** it SHALL load configuration from `phpunit.xml.dist`
- **AND** the configuration SHALL define test suites
- **AND** the configuration SHALL specify the bootstrap file
- **AND** the configuration SHALL configure coverage settings

### Requirement: No Pest Dependencies
The project SHALL NOT include Pest or Pest-related dependencies.

#### Scenario: Composer dependencies
- **GIVEN** the `composer.json` file
- **WHEN** reviewing dependencies
- **THEN** it SHALL NOT include `pestphp/pest` in `require-dev`
- **AND** it SHALL NOT include Pest plugins (e.g., `spatie/pest-plugin-snapshots`)
- **AND** it SHALL NOT include Pest plugin configuration in the `config` section

#### Scenario: Test execution command
- **GIVEN** the composer scripts section
- **WHEN** defining test commands
- **THEN** the `test` script SHALL invoke `vendor/bin/phpunit`
- **AND** it SHALL NOT invoke `vendor/bin/pest`

### Requirement: Backward Compatibility Stubs
During migration, the framework SHALL provide temporary compatibility stubs for Laravel components when needed.

#### Scenario: Laravel stub classes
- **GIVEN** tests that reference Laravel classes during migration
- **WHEN** the class is not available
- **THEN** a stub implementation MAY be provided in `tests/Stubs/`
- **AND** the stub SHALL provide minimal compatibility
- **AND** the stub SHALL be removed once migration is complete

#### Scenario: JsonResource stub
- **GIVEN** tests using Laravel's JsonResource
- **WHEN** the test is executed
- **THEN** a `JsonResource` stub SHALL be loaded from `tests/Stubs/JsonResource.php`
- **AND** the stub SHALL be loaded conditionally only if the class doesn't exist
- **AND** this stub SHALL be removed once all Laravel dependencies are eliminated

### Requirement: Test fixtures SHALL use Doctrine ORM entities

Test data fixtures SHALL be implemented as Doctrine ORM entities with proper mapping annotations.

#### Scenario: Entity fixtures are properly annotated
- **GIVEN** a test fixture entity class
- **WHEN** the entity is defined
- **THEN** it SHALL use Doctrine ORM attributes (`#[ORM\Entity]`, `#[ORM\Column]`, etc.)
- **AND** it SHALL be located in `tests/Fixtures/Entities/` directory
- **AND** it SHALL include necessary getters and setters for test access

#### Scenario: Doctrine mapping is configured for test entities
- **GIVEN** test entities in `tests/Fixtures/Entities/`
- **WHEN** tests are executed
- **THEN** Doctrine SHALL auto-discover the test entities
- **AND** the ORM SHALL be configured in `SymfonyTestCase::createKernel()`
- **AND** entities SHALL be available for type inference and schema generation tests

### Requirement: No Laravel migration artifacts SHALL remain

Test directory SHALL be free of backup files and temporary files from framework migrations.

#### Scenario: Backup files are removed
- **GIVEN** the test directory
- **WHEN** files are listed
- **THEN** there SHALL be NO files matching `*.backup`
- **AND** there SHALL be NO files matching `*.bak`
- **AND** there SHALL be NO files matching `*.new`
- **AND** there SHALL be NO files matching `*.laravel-backup`

#### Scenario: Active test files have no Pest syntax
- **GIVEN** an active test file (not a backup)
- **WHEN** the file is parsed
- **THEN** it SHALL NOT contain `test(` function calls
- **AND** it SHALL NOT contain Pest's `expect()->toBe()` chains
- **AND** it SHALL NOT contain `it(` function calls
- **AND** it SHALL NOT contain `describe(` function calls

### Requirement: Test snapshots SHALL reflect Symfony/Doctrine types

Test snapshots SHALL contain type information from Symfony and Doctrine, not Laravel/Illuminate.

#### Scenario: Snapshots do not reference Laravel namespaces
- **GIVEN** a test snapshot file
- **WHEN** the snapshot content is examined
- **THEN** it SHALL NOT contain `Illuminate\` namespace references
- **AND** it SHALL NOT contain Laravel-specific class names
- **AND** type information SHALL use Doctrine or Symfony equivalents

