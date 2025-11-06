## ADDED Requirements

### Requirement: Symfony Route Collection Parsing
The system SHALL extract routes from Symfony's RouteCollection for documentation generation.

#### Scenario: Route collection retrieval
- **WHEN** generating OpenAPI documentation
- **THEN** the system retrieves all routes from the RouterInterface
- **AND** filters routes based on configured path prefixes (e.g., `/api`)
- **AND** filters routes based on configured domain patterns

#### Scenario: Route metadata extraction
- **WHEN** processing a Symfony route
- **THEN** the system extracts the route path pattern
- **AND** extracts allowed HTTP methods
- **AND** extracts route defaults (controller, action, parameters)
- **AND** extracts route requirements (regex constraints)
- **AND** extracts route condition expressions

### Requirement: Route Attribute Support
The system SHALL support PHP attributes for defining route metadata on controllers and methods.

#### Scenario: Controller route attributes
- **WHEN** a controller method uses `#[Route]` attributes
- **THEN** the system detects the route definition
- **AND** extracts the path, methods, and name from attributes
- **AND** merges class-level and method-level route attributes

#### Scenario: Custom metadata attributes
- **WHEN** a controller method uses Scramble-specific attributes (e.g., `#[Response]`, `#[Parameter]`)
- **THEN** the system applies this metadata to the generated OpenAPI operation
- **AND** attributes override inferred metadata
- **AND** multiple attributes of the same type are combined

### Requirement: Controller Reflection
The system SHALL analyze Symfony controller methods to extract API endpoint information.

#### Scenario: Controller invokable
- **WHEN** a route points to an invokable controller
- **THEN** the system analyzes the `__invoke` method
- **AND** extracts method signature and return type
- **AND** extracts PHPDoc annotations

#### Scenario: Controller action method
- **WHEN** a route points to a controller action method
- **THEN** the system resolves the controller class and method
- **AND** analyzes method parameters and return types
- **AND** infers request/response types from the method signature

### Requirement: Route Parameter Resolution
The system SHALL map Symfony route parameters to OpenAPI path parameters.

#### Scenario: Simple path parameters
- **WHEN** a route has a parameter like `/users/{id}`
- **THEN** the system creates an OpenAPI path parameter named `id`
- **AND** infers the parameter type from controller method signature
- **AND** marks the parameter as required

#### Scenario: Parameter constraints
- **WHEN** a route parameter has a requirement like `\d+`
- **THEN** the system adds pattern constraint to the OpenAPI parameter
- **AND** may infer integer type from numeric pattern
- **AND** adds format specifications when applicable

#### Scenario: Optional parameters with defaults
- **WHEN** a route parameter has a default value
- **THEN** the OpenAPI parameter is marked as not required
- **AND** the default value is documented in the parameter schema
