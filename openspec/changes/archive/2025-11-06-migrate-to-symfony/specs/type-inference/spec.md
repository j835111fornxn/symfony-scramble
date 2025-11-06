## ADDED Requirements

### Requirement: Doctrine Entity Metadata Extraction
The system SHALL analyze Doctrine entities to infer OpenAPI schema properties.

#### Scenario: Entity field type inference
- **WHEN** analyzing a Doctrine entity
- **THEN** the system uses EntityManagerInterface to get class metadata
- **AND** extracts field types from `@ORM\Column` attributes
- **AND** maps Doctrine types to OpenAPI types (string, integer, boolean, etc.)
- **AND** includes format information (date, datetime, uuid, etc.)

#### Scenario: Entity relationship inference
- **WHEN** an entity has associations (ManyToOne, OneToMany, etc.)
- **THEN** the system detects the relationship type
- **AND** creates references to related entity schemas
- **AND** handles nullable associations correctly
- **AND** includes relationship metadata in descriptions

#### Scenario: Entity nullability
- **WHEN** an entity field has nullable=true
- **THEN** the field is not included in required properties array
- **WHEN** an entity field has nullable=false
- **THEN** the field is included in required properties array
- **AND** the schema does not allow null values

### Requirement: Symfony Serializer Integration
The system SHALL use Symfony Serializer metadata to determine response structure.

#### Scenario: Serialization groups
- **WHEN** a controller method specifies serialization groups (via context or attributes)
- **THEN** only properties in those groups are included in the OpenAPI schema
- **AND** properties without groups or from other groups are excluded
- **AND** group inheritance is respected

#### Scenario: Custom normalizers
- **WHEN** custom normalizers are registered for types
- **THEN** the system attempts to infer the normalized structure
- **AND** falls back to generic object schema if inference fails
- **AND** allows extension points for custom normalizer inference

#### Scenario: Serializer attributes
- **WHEN** properties use `#[SerializedName]` attributes
- **THEN** the OpenAPI schema uses the serialized name
- **WHEN** properties use `#[Ignore]` attributes
- **THEN** those properties are excluded from the schema
- **WHEN** properties use `#[Context]` attributes
- **THEN** context settings influence schema generation

### Requirement: Request Parameter Type Inference
The system SHALL infer request parameter types from controller method signatures and request objects.

#### Scenario: Type-hinted parameters
- **WHEN** a controller method parameter has a PHP type hint
- **THEN** the system uses that type for the OpenAPI parameter schema
- **AND** converts PHP types to OpenAPI types (int → integer, string → string)
- **AND** handles nullable types with `?` or union types with `null`

#### Scenario: Request object parameters
- **WHEN** a controller accepts a Request object
- **THEN** the system analyzes request query parameters via validation or attributes
- **AND** extracts parameter metadata from bound classes or form types
- **AND** documents both query and body parameters

#### Scenario: Default parameter values
- **WHEN** a controller method parameter has a default value
- **THEN** the parameter is marked as optional in OpenAPI
- **AND** the default value is documented in the schema
- **AND** the parameter type is inferred from the default value if not explicitly typed

### Requirement: Response Type Inference
The system SHALL infer response types from controller return types and annotations.

#### Scenario: Return type hints
- **WHEN** a controller method has a return type
- **THEN** the system analyzes the returned type for schema generation
- **AND** handles Response, JsonResponse, and custom response types
- **AND** extracts actual data type from response wrappers

#### Scenario: PHPDoc return tags
- **WHEN** a controller method has `@return` PHPDoc
- **THEN** the system uses PHPDoc type information for more detailed inference
- **AND** PHPDoc takes precedence over simple return type hints
- **AND** supports complex generic types (array<User>, Collection<Product>)

#### Scenario: Symfony Response objects
- **WHEN** a controller returns a Symfony Response object
- **THEN** the system attempts to infer the response content type
- **AND** uses serializer metadata if the response contains an entity
- **AND** handles streaming and binary responses appropriately

### Requirement: Custom Type Extensions
The system SHALL provide extension points for custom type inference logic.

#### Scenario: Type resolver extension
- **WHEN** a custom type resolver extension is registered
- **THEN** it is invoked during type inference
- **AND** can provide custom type information for specific classes
- **AND** can override default inference behavior

#### Scenario: Method return type extension
- **WHEN** analyzing a method return type
- **THEN** registered return type extensions are consulted
- **AND** extensions can provide more specific type information
- **AND** extensions can handle framework-specific patterns (e.g., repository methods)

### Requirement: Doctrine Type Conversions
The system SHALL correctly map Doctrine types to OpenAPI types and formats.

#### Scenario: Common Doctrine types
- **WHEN** a field is of type `string`
- **THEN** OpenAPI type is `string` with no format
- **WHEN** a field is of type `integer`
- **THEN** OpenAPI type is `integer` with format `int32`
- **WHEN** a field is of type `bigint`
- **THEN** OpenAPI type is `integer` with format `int64`
- **WHEN** a field is of type `boolean`
- **THEN** OpenAPI type is `boolean`
- **WHEN** a field is of type `decimal` or `float`
- **THEN** OpenAPI type is `number` with format `float` or `double`

#### Scenario: Date and time types
- **WHEN** a field is of type `date`
- **THEN** OpenAPI type is `string` with format `date`
- **WHEN** a field is of type `datetime` or `datetime_immutable`
- **THEN** OpenAPI type is `string` with format `date-time`
- **WHEN** a field is of type `time`
- **THEN** OpenAPI type is `string` with format `time`

#### Scenario: Special types
- **WHEN** a field is of type `json`
- **THEN** OpenAPI type is `object` or `array` depending on schema
- **WHEN** a field is of type `uuid`
- **THEN** OpenAPI type is `string` with format `uuid`
- **WHEN** a field is of type `text`
- **THEN** OpenAPI type is `string` with no format but may include maxLength
