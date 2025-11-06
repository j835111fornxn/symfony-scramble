# middleware Specification

## Purpose
TBD - created by archiving change migrate-to-symfony. Update Purpose after archive.
## Requirements
### Requirement: Event-Based Access Control
The system SHALL use Symfony event subscribers instead of middleware for access control to documentation routes.

#### Scenario: Request event subscriber
- **WHEN** a request is made to a documentation route
- **THEN** a `kernel.request` event subscriber checks authorization
- **AND** uses Symfony Security component to verify access
- **AND** throws AccessDeniedException if unauthorized
- **AND** allows the request to proceed if authorized

#### Scenario: Configurable access control
- **WHEN** documentation access control is configured
- **THEN** the system respects configured roles or voters
- **AND** allows custom security expressions
- **AND** supports environment-based restrictions (e.g., dev-only)

### Requirement: OpenAPI Generation Event Hooks
The system SHALL provide event hooks throughout the OpenAPI generation process for customization.

#### Scenario: Pre-generation event
- **WHEN** OpenAPI document generation begins
- **THEN** a `scramble.generation.start` event is dispatched
- **AND** listeners can modify generator configuration
- **AND** listeners can add custom routes to process

#### Scenario: Post-generation event
- **WHEN** OpenAPI document generation completes
- **THEN** a `scramble.generation.complete` event is dispatched
- **AND** listeners receive the complete OpenAPI document
- **AND** listeners can modify the document before rendering

#### Scenario: Operation generation event
- **WHEN** an individual operation is generated for a route
- **THEN** a `scramble.operation.generated` event is dispatched
- **AND** listeners can modify operation metadata
- **AND** listeners can add custom parameters or responses

### Requirement: Exception Handling Events
The system SHALL use kernel exception events to handle documentation generation errors.

#### Scenario: Generation error handling
- **WHEN** an error occurs during documentation generation
- **THEN** a `kernel.exception` event subscriber catches the exception
- **AND** formats the error appropriately for the response
- **AND** logs the error with sufficient context
- **AND** returns appropriate HTTP status code

#### Scenario: Development vs production error display
- **WHEN** an error occurs in development mode
- **THEN** the full exception details are shown in the documentation
- **AND** stack traces are included for debugging
- **WHEN** an error occurs in production mode
- **THEN** generic error messages are shown
- **AND** sensitive details are not exposed

