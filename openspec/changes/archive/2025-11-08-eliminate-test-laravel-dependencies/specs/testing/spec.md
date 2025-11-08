# testing Specification Delta

## ADDED Requirements

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

## MODIFIED Requirements

None.

## REMOVED Requirements

None.
