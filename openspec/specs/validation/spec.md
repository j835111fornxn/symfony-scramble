# validation Specification

## Purpose
TBD - created by archiving change migrate-to-symfony. Update Purpose after archive.
## Requirements
### Requirement: Symfony Validator Constraint Inference
The system SHALL extract validation rules from Symfony Validator constraints for OpenAPI parameter documentation.

#### Scenario: Property constraint extraction
- **WHEN** analyzing a class with validation constraints
- **THEN** the system uses ValidatorInterface to get property metadata
- **AND** extracts constraint types (NotBlank, Length, Email, etc.)
- **AND** converts constraints to OpenAPI schema properties

#### Scenario: Attribute-based constraints
- **WHEN** a property uses constraint attributes like `#[Assert\NotBlank]`
- **THEN** the system detects and processes the constraint
- **AND** marks the property as required in OpenAPI schema
- **AND** includes constraint details in schema validation rules

#### Scenario: YAML/XML constraint configuration
- **WHEN** constraints are defined in YAML or XML files
- **THEN** the system loads constraint metadata from ValidatorInterface
- **AND** applies the same inference logic as attribute-based constraints
- **AND** supports all standard Symfony constraints

### Requirement: Constraint to Schema Conversion
The system SHALL convert Symfony validation constraints to equivalent OpenAPI schema validations.

#### Scenario: String constraints
- **WHEN** a property has Length constraint
- **THEN** minLength and maxLength are set in OpenAPI schema
- **WHEN** a property has Regex constraint
- **THEN** pattern is set in OpenAPI schema
- **WHEN** a property has Email constraint
- **THEN** format is set to "email" in OpenAPI schema

#### Scenario: Numeric constraints
- **WHEN** a property has Range constraint
- **THEN** minimum and maximum are set in OpenAPI schema
- **WHEN** a property has GreaterThan/LessThan constraints
- **THEN** exclusiveMinimum/exclusiveMaximum are set
- **WHEN** a property has Positive/Negative constraints
- **THEN** appropriate minimum/maximum values are set

#### Scenario: Collection constraints
- **WHEN** a property has Count constraint
- **THEN** minItems and maxItems are set in OpenAPI schema
- **WHEN** a property has Unique constraint
- **THEN** uniqueItems is set to true in OpenAPI schema

### Requirement: Form Type Integration
The system SHALL analyze Symfony Form types to extract request body schema.

#### Scenario: Form type field inference
- **WHEN** a controller action accepts a Form type
- **THEN** the system analyzes the form's buildForm method
- **AND** extracts field names and types
- **AND** determines which fields are required
- **AND** extracts field constraints and options

#### Scenario: Nested form types
- **WHEN** a form type contains nested form types
- **THEN** the system recursively analyzes nested forms
- **AND** creates nested object schemas in OpenAPI
- **AND** preserves validation rules at each level

#### Scenario: Collection form fields
- **WHEN** a form contains a CollectionType field
- **THEN** the OpenAPI schema defines an array type
- **AND** the array items schema matches the entry_type
- **AND** min/max entry options map to minItems/maxItems

### Requirement: Constraint Validation Groups
The system SHALL support Symfony validation groups for contextual validation documentation.

#### Scenario: Default validation group
- **WHEN** no validation groups are specified
- **THEN** the system documents constraints from the Default group
- **AND** all constraints without explicit groups are included

#### Scenario: Explicit validation groups
- **WHEN** a controller specifies validation groups (e.g., via attribute)
- **THEN** only constraints from those groups are documented
- **AND** constraints from other groups are excluded
- **AND** this allows different validation for different endpoints

#### Scenario: Group sequences
- **WHEN** a GroupSequence is defined
- **THEN** the system documents validation in sequence order
- **AND** includes notes about conditional validation

