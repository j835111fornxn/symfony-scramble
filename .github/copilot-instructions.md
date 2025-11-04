# Copilot Instructions for AI Coding Agents

## Project Overview
- **Scramble** is an API documentation generator for Laravel projects, producing OpenAPI 3.1.0 docs automatically from code, without requiring manual PHPDoc annotations.
- The codebase is organized around extracting, inferring, and transforming API metadata from Laravel routes and controllers.

## Key Architecture & Components
- **src/**: Core logic. Notable files:
  - `Scramble.php`, `ScrambleServiceProvider.php`: Laravel integration, service registration.
  - `Generator.php`, `GeneratorConfig.php`: Main doc generation pipeline and config.
  - `OpenApiTraverser.php`, `OpenApiVisitor.php`: Traverse and visit code structures to extract API info.
  - `Attributes/`: Custom PHP attributes for API documentation (e.g., `Endpoint`, `Response`, `Parameter`).
  - `Configuration/`: Extensible config for transformers, extractors, etc.
  - `DocumentTransformers/`, `Infer/`, `PhpDoc/`, `Reflection/`: Specialized logic for doc generation and type inference.
- **routes/web.php**: Defines the `/docs/api` and `/docs/api.json` endpoints.
- **config/scramble.php**: Main configuration file for customizing behavior.

## Developer Workflows
- **Install**: `composer require dedoc/scramble`
- **Test**: Run tests with `vendor/bin/phpunit` (uses `phpunit.xml.dist`).
- **Type Checking**: Use `phpstan` with configs in `phpstan.neon` and baseline in `phpstan-baseline.neon`.
- **Docs Generation**: Triggered via Laravel routes; see `routes/web.php` and documentation at [scramble.dedoc.co](https://scramble.dedoc.co).

## Project-Specific Patterns
- **No manual PHPDoc required**: Documentation is inferred from code and custom attributes.
- **Extensibility**: Add new document/operation transformers in `src/DocumentTransformers/` and `src/Configuration/`.
- **Attributes**: Use PHP attributes in controllers/models to influence documentation (see `src/Attributes/`).
- **Error Handling**: Custom exceptions in `src/Exceptions/` (e.g., `InvalidSchema.php`).
- **Testing**: Tests are in `tests/`, organized by feature/component. Use snapshot testing for docs output.

## Integration Points
- **Laravel**: Registered as a service provider; hooks into route and controller reflection.
- **OpenAPI**: Output is OpenAPI 3.1.0 JSON, served via `/docs/api.json`.
- **Customization**: Extend via config files and custom transformers/extractors.

## Example Patterns
- To add a custom header parameter:
  ```php
  use Dedoc\Scramble\Attributes\HeaderParameter;
  #[HeaderParameter('X-Custom-Header', type: 'string')]
  ```
- To exclude a route from docs:
  ```php
  use Dedoc\Scramble\Attributes\ExcludeRouteFromDocs;
  #[ExcludeRouteFromDocs]
  ```

## References
- [README.md](../README.md)
- [scramble.dedoc.co](https://scramble.dedoc.co)
- `src/Attributes/`, `src/DocumentTransformers/`, `config/scramble.php`, `routes/web.php`

---
For unclear or missing conventions, review the above files and documentation site. Ask maintainers for project-specific patterns not covered here.
