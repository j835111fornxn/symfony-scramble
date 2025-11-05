<p>
  <a href="https://scramble.dedoc.co" target="_blank">
    <img src="./.github/gh-img.png?v=1" alt="Scramble – Symfony API documentation generator"/>
  </a>
</p>

# Scramble for Symfony

Scramble generates API documentation for Symfony projects. Without requiring you to manually write PHPDoc annotations. Docs are generated in OpenAPI 3.1.0 format.

> **Note**: This is a Symfony port of the original Laravel Scramble package. The core functionality has been adapted to work with Symfony's architecture while maintaining the same powerful automatic documentation generation capabilities.

## Documentation

You can find full documentation at [scramble.dedoc.co](https://scramble.dedoc.co).

## Introduction

The main motto of the project is generating your API documentation without requiring you to annotate your code.

This allows you to focus on code and avoid annotating every possible param/field as it may result in outdated documentation. By generating docs automatically from the code your API will always have up-to-date docs which you can trust.

## Requirements

- PHP 8.1 or higher
- Symfony 6.4 or 7.0
- Doctrine ORM (optional, for entity documentation)

## Installation

You can install the package via composer:

```shell
composer require dedoc/scramble
```

### Register the Bundle

Add the bundle to your `config/bundles.php`:

```php
return [
    // ... other bundles
    Dedoc\Scramble\ScrambleBundle::class => ['all' => true],
];
```

### Configuration

Create a configuration file at `config/packages/scramble.yaml`:

```yaml
scramble:
    api_path: 'api'              # Base path for API routes to document
    api_domain: ~                # Optional: specific domain for API routes
    info:
        title: 'My API'
        version: '1.0.0'
        description: 'API Documentation'
```

## Usage

After installation, you will have 2 routes available:

- `/docs/api` - UI viewer for your documentation
- `/docs/api.json` - OpenAPI document in JSON format describing your API

### Access Control

By default, these routes are available in all environments. You can restrict access using Symfony's security system:

```yaml
# config/packages/security.yaml
security:
    access_control:
        - { path: ^/docs/api, roles: ROLE_ADMIN }
```

### Documenting Your API

Scramble automatically analyzes your Symfony controllers and generates documentation. Here's an example:

```php
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/users', name: 'api_users_')]
class UserController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        // Scramble will automatically document this endpoint
        return $this->json(['users' => []]);
    }
    
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        // Path parameters are automatically documented
        return $this->json(['user' => ['id' => $id]]);
    }
}
```

### Using Attributes for Enhanced Documentation

You can use Scramble's attributes to provide additional information:

```php
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Response;

#[Route('/api/users/{id}', methods: ['GET'])]
#[Endpoint(
    summary: 'Get user details',
    description: 'Retrieve detailed information about a specific user'
)]
#[Response(200, description: 'User found', content: User::class)]
#[Response(404, description: 'User not found')]
public function show(int $id): JsonResponse
{
    // Your implementation
}
```

### Working with Doctrine Entities

Scramble automatically generates schemas from your Doctrine entities:

```php
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $email;

    // Getters and setters...
}
```

When you return this entity (or collections of entities) from your controllers, Scramble will automatically generate the appropriate schema in your OpenAPI documentation.

---

<p>
  <a href="https://savelife.in.ua/en/donate-en/" target="_blank">
    <img src="./.github/gh-promo.svg?v=1" alt="Donate"/>
  </a>
</p> 
