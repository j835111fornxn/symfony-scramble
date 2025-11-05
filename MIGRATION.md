# Migration Guide: Laravel to Symfony

This guide helps you migrate from Laravel Scramble to Symfony Scramble.

## Overview

Scramble has been successfully ported from Laravel to Symfony while maintaining its core functionality. The automatic documentation generation works the same way, but the integration points have changed to match Symfony's architecture.

## Key Changes

### 1. Service Provider → Bundle

**Laravel:**
```php
// Automatically registered via package discovery
// or in config/app.php
'providers' => [
    Dedoc\Scramble\ScrambleServiceProvider::class,
]
```

**Symfony:**
```php
// config/bundles.php
return [
    Dedoc\Scramble\ScrambleBundle::class => ['all' => true],
];
```

### 2. Configuration

**Laravel:**
```php
// config/scramble.php
return [
    'api_path' => 'api',
    'api_domain' => null,
    // ...
];
```

**Symfony:**
```yaml
# config/packages/scramble.yaml
scramble:
    api_path: 'api'
    api_domain: ~
    info:
        title: 'My API'
        version: '1.0.0'
```

### 3. Routing

**Laravel:**
- Routes in `routes/api.php`
- Uses Laravel's routing system

**Symfony:**
- Routes using Symfony annotations/attributes
- Uses Symfony's routing component

```php
// Symfony example
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/users', name: 'api_users_', methods: ['GET'])]
class UserController extends AbstractController
{
    // ...
}
```

### 4. Middleware → Security

**Laravel:**
```php
Route::middleware('auth:api')->group(function () {
    // Routes
});
```

**Symfony:**
```yaml
# config/packages/security.yaml
security:
    access_control:
        - { path: ^/api, roles: ROLE_USER }
```

### 5. Models → Entities

**Laravel:**
- Eloquent Models
- Automatic model detection via `Illuminate\Database\Eloquent\Model`

**Symfony:**
- Doctrine Entities
- Uses Doctrine ORM metadata
- Automatic entity detection via `@ORM\Entity`

**Migration Example:**

```php
// Laravel Model
class User extends Model
{
    protected $fillable = ['name', 'email'];
}

// Symfony Entity
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $email;

    // Getters and setters required
}
```

### 6. Request Validation

**Laravel:**
- Form Requests
- Validation rules in `rules()` method

**Symfony:**
- Symfony Validator constraints
- Constraints on entity properties or DTOs

```php
// Symfony example
use Symfony\Component\Validator\Constraints as Assert;

class CreateUserRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    public string $name;

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;
}
```

### 7. Response Resources → Serialization

**Laravel:**
```php
return new UserResource($user);
```

**Symfony:**
```php
// Use Symfony Serializer
return $this->json($user, 200, [], [
    'groups' => ['user:read']
]);
```

### 8. Exceptions

**Laravel Exceptions → Symfony Exceptions:**

- `ModelNotFoundException` → `NotFoundHttpException`
- `AuthenticationException` → `UnauthorizedHttpException` 
- `AuthorizationException` → `AccessDeniedHttpException`
- `ValidationException` → `ValidationFailedException`

## Features Maintained

✅ **Automatic Documentation Generation** - Still works without manual annotations

✅ **OpenAPI 3.1.0 Format** - Same output format

✅ **Type Inference** - Advanced PHP type analysis preserved

✅ **Path Parameters** - Automatically detected from routes

✅ **Request Body** - Inferred from controller parameters

✅ **Response Schemas** - Generated from return types

✅ **Attributes Support** - Same attribute classes available:
- `@Endpoint`
- `@Response`
- `@PathParameter`
- `@QueryParameter`
- `@HeaderParameter`
- etc.

## Optional Laravel Features

Some Laravel-specific features are optionally available when Laravel packages are installed:

- **Eloquent Models**: Works if `illuminate/database` is installed
- **Laravel Responses**: Recognizes `Illuminate\Http\Response` if available
- **Form Requests**: Can parse Laravel Form Request classes

These features gracefully degrade if the packages aren't available.

## Step-by-Step Migration

### 1. Update Dependencies

```json
{
    "require": {
        "symfony/framework-bundle": "^6.4|^7.0",
        "symfony/routing": "^6.4|^7.0",
        "doctrine/orm": "^2.17|^3.0",
        "dedoc/scramble": "^2.0"
    }
}
```

### 2. Replace ServiceProvider with Bundle

Remove Laravel service provider references, add Bundle registration.

### 3. Convert Configuration

Transform `config/scramble.php` to `config/packages/scramble.yaml`.

### 4. Update Controllers

- Add Symfony routing attributes
- Use Symfony controller base class
- Update dependency injection

### 5. Migrate Models to Entities

- Convert Eloquent models to Doctrine entities
- Add proper Doctrine annotations/attributes
- Implement getters and setters

### 6. Update Validation

- Convert Form Requests to Symfony validation
- Use Symfony Validator constraints

### 7. Test Documentation

Visit `/docs/api` to verify documentation is generating correctly.

## Breaking Changes

### Removed Features

- ❌ **Laravel Passport/Sanctum Integration** - Use Symfony Security instead
- ❌ **Laravel Gate Checks** - Use Symfony Security voters
- ❌ **Blade Views** - Uses Twig (but Scramble provides its own UI)

### Changed Behavior

- Route filtering now based on Symfony route configuration
- Middleware detection replaced with security attribute detection
- Model detection uses Doctrine metadata instead of Eloquent

## Troubleshooting

### Documentation Not Generating

1. Check bundle is registered in `config/bundles.php`
2. Verify routes are accessible
3. Check logs in `var/log/dev.log`

### Missing Schemas

1. Ensure Doctrine entities have proper annotations
2. Check return types are properly type-hinted
3. Verify entities are in a scanned namespace

### Access Denied

1. Check security configuration in `config/packages/security.yaml`
2. Verify you're authenticated if routes are protected

## Support

If you encounter issues during migration:

1. Check the [documentation](https://scramble.dedoc.co)
2. Review example Symfony projects (coming soon)
3. Open an issue on GitHub

## Example Projects

See complete examples in:
- [symfony-scramble-demo](https://github.com/dedoc/symfony-scramble-demo) (coming soon)

## Credits

Original Laravel version: [dedoc/scramble](https://github.com/dedoc/scramble)

Symfony port maintains the same philosophy and core functionality, adapted for Symfony's architecture.
