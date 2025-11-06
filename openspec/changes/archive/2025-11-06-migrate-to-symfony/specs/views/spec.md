## ADDED Requirements

### Requirement: Twig Template Rendering
The system SHALL use Twig template engine for rendering the API documentation UI.

#### Scenario: Template rendering
- **WHEN** documentation UI route is accessed
- **THEN** the system renders the documentation using a Twig template
- **AND** passes the OpenAPI specification as a template variable
- **AND** passes configuration options to the template

#### Scenario: Twig environment configuration
- **WHEN** the bundle is initialized
- **THEN** Twig is configured with the template path for Scramble
- **AND** the `@Scramble` namespace is registered for templates
- **AND** templates can be overridden in the application

### Requirement: Template Variables
The system SHALL provide necessary variables to the Twig template for rendering documentation.

#### Scenario: OpenAPI spec variable
- **WHEN** rendering the documentation template
- **THEN** the `spec` variable contains the complete OpenAPI document
- **AND** the spec is properly escaped for JSON embedding
- **AND** the spec is passed to the Stoplight Elements component

#### Scenario: Configuration variables
- **WHEN** rendering the documentation template
- **THEN** UI configuration options are available (theme, layout, logo)
- **AND** the application name is available for the page title
- **AND** custom CSS/JS paths are available if configured

### Requirement: Template Inheritance and Overriding
The system SHALL allow applications to override or extend the default documentation template.

#### Scenario: Template override
- **WHEN** an application defines a template at `templates/bundles/ScrambleBundle/docs.html.twig`
- **THEN** the custom template is used instead of the default
- **AND** the custom template receives all the same variables
- **AND** the application can completely customize the UI

#### Scenario: Template extension
- **WHEN** a custom template extends the default template
- **THEN** the custom template can override specific blocks
- **AND** can reuse default block content via parent()
- **AND** can add additional assets or configuration
