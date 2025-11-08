# testing Specification Delta

This document describes changes to the testing specification to add coverage requirements.

## ADDED Requirements

### Requirement: Code Coverage Standards
The test suite SHALL maintain a minimum of 90% line coverage across the entire codebase.

#### Scenario: Running coverage report
- **GIVEN** a developer wants to check test coverage
- **WHEN** they run `composer test-coverage`
- **THEN** PHPUnit SHALL generate a coverage report
- **AND** the report SHALL show line coverage percentage
- **AND** the report SHALL be available in HTML, text, and Clover XML formats

#### Scenario: Coverage threshold enforcement
- **GIVEN** the test suite is executed with coverage enabled
- **WHEN** the coverage report is generated
- **THEN** the overall line coverage SHALL be at least 90%
- **AND** critical components (Generator, Infer, TypeTransformer) SHALL have at least 95% coverage

#### Scenario: Coverage reporting in CI
- **GIVEN** tests are run in CI/CD pipeline
- **WHEN** the test job completes
- **THEN** the coverage report SHALL be generated
- **AND** the build SHALL fail if coverage drops below 90%
- **AND** coverage metrics SHALL be published in the build artifacts

### Requirement: Coverage Configuration
PHPUnit SHALL be configured to collect and report code coverage using pcov or xdebug.

#### Scenario: Coverage driver configuration
- **GIVEN** PHPUnit is executed with coverage enabled
- **WHEN** coverage is collected
- **THEN** the system SHALL use pcov driver if available
- **OR** fall back to xdebug if pcov is not installed
- **AND** coverage SHALL be collected only for source files in `src/` directory

#### Scenario: Coverage report formats
- **GIVEN** coverage is enabled in `phpunit.xml.dist`
- **WHEN** tests are executed
- **THEN** coverage SHALL be available in the following formats:
  - HTML report in `build/coverage/` directory
  - Clover XML in `build/coverage/clover.xml`
  - Text summary to stdout

#### Scenario: Coverage exclusions
- **GIVEN** certain files should be excluded from coverage
- **WHEN** marking code with `@codeCoverageIgnore` annotation
- **THEN** the annotated code SHALL NOT count toward coverage metrics
- **AND** exclusions SHALL be documented with justification

### Requirement: Test Organization
Tests SHALL be organized to mirror the source code structure with dedicated test directories for each source component.

#### Scenario: Test directory structure
- **GIVEN** a source file at `src/ComponentName/ClassName.php`
- **WHEN** creating tests for this file
- **THEN** tests SHALL be placed in `tests/ComponentName/ClassNameTest.php`
- **AND** the test class SHALL be named `ClassNameTest`
- **AND** the test class SHALL extend appropriate base test class

#### Scenario: Complete source coverage
- **GIVEN** all source directories in `src/`
- **WHEN** reviewing test directories in `tests/`
- **THEN** each source directory SHALL have a corresponding test directory
- **AND** each major source file SHALL have at least one test file
- **AND** the following directories SHALL have comprehensive tests:
  - Attributes
  - Configuration
  - DependencyInjection
  - DocumentTransformers
  - Event
  - EventSubscriber
  - Exceptions
  - Extensions
  - Http
  - Infer (existing)
  - OpenApiVisitor
  - PhpDoc (existing)
  - Reflection
  - Support (existing)

### Requirement: Test Categories
The test suite SHALL include unit tests, integration tests, and edge case tests in appropriate proportions.

#### Scenario: Unit test coverage
- **GIVEN** a single class or component
- **WHEN** writing tests for it
- **THEN** unit tests SHALL test individual methods in isolation
- **AND** unit tests SHALL use mocks/stubs for dependencies
- **AND** unit tests SHALL be fast (< 100ms each)
- **AND** unit tests SHALL comprise at least 70% of total test count

#### Scenario: Integration test coverage
- **GIVEN** multiple components that interact
- **WHEN** testing component integration
- **THEN** integration tests SHALL use real Symfony container
- **AND** integration tests SHALL test complete workflows
- **AND** integration tests SHALL verify end-to-end functionality
- **AND** integration tests SHALL comprise about 25% of total test count

#### Scenario: Edge case test coverage
- **GIVEN** error conditions and boundary cases
- **WHEN** writing tests for them
- **THEN** edge case tests SHALL cover null inputs
- **AND** edge case tests SHALL cover empty collections
- **AND** edge case tests SHALL cover invalid configurations
- **AND** edge case tests SHALL cover exception scenarios
- **AND** edge case tests SHALL comprise about 5% of total test count

### Requirement: Coverage Metrics
Multiple coverage metrics SHALL be tracked with line coverage as the primary enforced metric.

#### Scenario: Line coverage measurement
- **GIVEN** the test suite is executed
- **WHEN** coverage is calculated
- **THEN** line coverage SHALL measure percentage of executed lines
- **AND** line coverage SHALL be the primary metric for coverage requirements
- **AND** line coverage SHALL be at least 90%

#### Scenario: Branch coverage measurement
- **GIVEN** code with conditional branches (if/else, switch, ternary)
- **WHEN** coverage is calculated
- **THEN** branch coverage SHALL measure percentage of executed branches
- **AND** branch coverage SHALL be tracked but not strictly enforced
- **AND** branch coverage target SHALL be at least 80%

#### Scenario: Method coverage measurement
- **GIVEN** all methods in the codebase
- **WHEN** coverage is calculated
- **THEN** method coverage SHALL measure percentage of called methods
- **AND** method coverage SHALL be tracked but not strictly enforced
- **AND** method coverage target SHALL be at least 95%

### Requirement: Testing Patterns
Standard testing patterns SHALL be used for common scenarios to ensure consistency and maintainability.

#### Scenario: Testing attribute classes
- **GIVEN** an attribute class (DTO-like)
- **WHEN** writing tests for it
- **THEN** tests SHALL verify constructor parameters
- **AND** tests SHALL verify property values are set correctly
- **AND** tests SHALL verify default values for optional parameters
- **AND** tests SHALL be simple and straightforward

#### Scenario: Testing service classes
- **GIVEN** a service class registered in DI container
- **WHEN** writing tests for it
- **THEN** tests SHALL extend `SymfonyTestCase`
- **AND** tests SHALL retrieve service from container
- **AND** tests SHALL verify service functionality with real dependencies
- **AND** tests MAY use mocks for external dependencies

#### Scenario: Testing transformer classes
- **GIVEN** a DocumentTransformer or TypeToSchemaExtension
- **WHEN** writing tests for it
- **THEN** tests SHALL verify transformation logic
- **AND** tests SHALL use appropriate input fixtures
- **AND** tests SHALL verify output structure
- **AND** tests MAY use snapshot testing for complex outputs

### Requirement: Coverage Maintenance
Coverage standards SHALL be maintained through CI/CD enforcement and regular audits.

#### Scenario: Coverage enforcement in CI
- **GIVEN** a pull request with code changes
- **WHEN** CI runs the test suite
- **THEN** CI SHALL fail the build if coverage drops below 90%
- **AND** CI SHALL report coverage metrics in the PR
- **AND** CI SHALL provide coverage diff (change from base branch)

#### Scenario: Coverage reporting
- **GIVEN** test execution completes
- **WHEN** generating the coverage report
- **THEN** the report SHALL identify uncovered lines
- **AND** the report SHALL highlight files below threshold
- **AND** the report SHALL be human-readable (HTML format)
- **AND** the report SHALL be machine-readable (Clover XML format)

#### Scenario: Periodic coverage audits
- **GIVEN** the codebase evolves over time
- **WHEN** conducting monthly coverage reviews
- **THEN** uncovered code SHALL be identified
- **AND** coverage gaps SHALL be prioritized for testing
- **AND** coverage trends SHALL be tracked over time

## MODIFIED Requirements

### Requirement: PHPUnit as Primary Testing Framework SHALL Support Coverage Collection

The testing infrastructure SHALL use PHPUnit as the primary and only testing framework for all automated tests, including code coverage collection.

#### Scenario: Test execution with coverage
- **GIVEN** a developer wants to run tests with coverage
- **WHEN** they execute `composer test-coverage`
- **THEN** PHPUnit SHALL execute all tests
- **AND** PHPUnit SHALL collect coverage data using pcov or xdebug
- **AND** PHPUnit SHALL generate coverage reports in configured formats
- **AND** PHPUnit SHALL display coverage summary in console output

### Requirement: Test Configuration SHALL Include Coverage Settings

Test execution SHALL be configured through `phpunit.xml.dist`, including coverage settings.

#### Scenario: PHPUnit configuration file with coverage
- **GIVEN** the project root directory
- **WHEN** PHPUnit is executed
- **THEN** it SHALL load configuration from `phpunit.xml.dist`
- **AND** the configuration SHALL define coverage source paths
- **AND** the configuration SHALL define coverage report formats
- **AND** the configuration SHALL define coverage report output paths
- **AND** the configuration SHALL enable coverage collection when requested

## Related Specifications
- `test-framework-independence` - Coverage tooling must remain framework-independent
