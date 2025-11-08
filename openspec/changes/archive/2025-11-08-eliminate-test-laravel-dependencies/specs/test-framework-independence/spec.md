# test-framework-independence Specification Delta

## ADDED Requirements

### Requirement: Tests SHALL NOT depend on Laravel ORM (Eloquent) models

Tests SHALL NOT reference Laravel's Eloquent ORM models, and SHALL use Doctrine ORM entities or framework-agnostic test doubles instead.

#### Scenario: Test fixtures use Doctrine entities
- **GIVEN** a test requiring data model fixtures
- **WHEN** the test is defined
- **THEN** it SHALL use Doctrine ORM entities from `tests/Fixtures/Entities/`
- **AND** it SHALL NOT reference `Illuminate\Database\Eloquent\Model`
- **AND** it SHALL NOT import from `*\Fixtures\Laravel\Models\*`

#### Scenario: Type inference tests work with Doctrine
- **GIVEN** a test validating type inference for data models
- **WHEN** the test analyzes a model class
- **THEN** it SHALL analyze a Doctrine entity with `#[ORM\Entity]` attribute
- **AND** the inferred types SHALL reflect Doctrine property annotations
- **AND** it SHALL NOT analyze Laravel Eloquent models

### Requirement: Tests SHALL NOT depend on Laravel Collections or API Resources

Tests SHALL NOT use Laravel's Collection or JsonResource classes, and SHALL use Symfony/Doctrine equivalents or standard PHP arrays.

#### Scenario: Tests do not use Laravel Collections
- **GIVEN** a test working with collections of data
- **WHEN** the test processes the collection
- **THEN** it SHALL use Doctrine `ArrayCollection` or standard PHP arrays
- **AND** it SHALL NOT use `Illuminate\Support\Collection`

#### Scenario: API resource transformation uses Symfony Serializer
- **GIVEN** a test validating API resource transformation
- **WHEN** the test transforms data for API output
- **THEN** it SHALL use Symfony's Serializer component if needed
- **AND** it SHALL NOT use `Illuminate\Http\Resources\Json\JsonResource`
- **OR** the test SHALL be skipped/removed if JsonResource-specific

## MODIFIED Requirements

### Requirement: Tests SHALL NOT use Laravel Facades

Tests SHALL NOT rely on Laravel's Facade pattern in any form, and SHALL use direct service injection or Symfony service container instead.

_(Updated to explicitly prohibit all remaining Laravel facades including Validator)_

#### Scenario: Validator facade SHALL NOT be used
- **GIVEN** a test needing validation
- **WHEN** validation is performed
- **THEN** it SHALL use Symfony's Validator component
- **AND** it SHALL NOT use `Illuminate\Support\Facades\Validator`

## REMOVED Requirements

None.
