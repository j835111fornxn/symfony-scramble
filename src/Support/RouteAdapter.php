<?php

namespace Dedoc\Scramble\Support;

use Closure;
use Symfony\Component\Routing\Route as SymfonyRoute;

/**
 * Adapter to make Symfony Route compatible with Laravel Route interface.
 * This allows existing code to work with both Laravel and Symfony routes.
 */
class RouteAdapter
{
    private SymfonyRoute $route;

    private string $name;

    public function __construct(SymfonyRoute $route, string $name = '')
    {
        $this->route = $route;
        $this->name = $name;
    }

    /**
     * Get the URI associated with the route.
     */
    public function uri(): string
    {
        return ltrim($this->route->getPath(), '/');
    }

    /**
     * Get the HTTP methods for the route.
     *
     * @return string[]
     */
    public function methods(): array
    {
        $methods = $this->route->getMethods();

        return ! empty($methods) ? $methods : ['GET', 'HEAD'];
    }

    /**
     * Get the route name.
     */
    public function getName(): ?string
    {
        return $this->name ?: null;
    }

    /**
     * Get the action array for the route.
     *
     * @return array<string, mixed>
     */
    public function getAction(?string $key = null): mixed
    {
        $defaults = $this->route->getDefaults();
        $action = [
            'uses' => $defaults['_controller'] ?? null,
            'controller' => $defaults['_controller'] ?? null,
            'middleware' => $defaults['_middleware'] ?? [],
        ];

        if ($key === null) {
            return $action;
        }

        return $action[$key] ?? null;
    }

    /**
     * Get the underlying Symfony route.
     */
    public function getSymfonyRoute(): SymfonyRoute
    {
        return $this->route;
    }

    /**
     * Get route parameter names.
     *
     * @return string[]
     */
    public function parameterNames(): array
    {
        $path = $this->route->getPath();
        preg_match_all('/\{(\w+)\}/', $path, $matches);

        return $matches[1] ?? [];
    }

    /**
     * Get the domain defined for the route.
     */
    public function domain(): ?string
    {
        return $this->route->getHost() ?: null;
    }

    /**
     * Get signature parameters (for reflection).
     *
     * @param  string|array|null  $subClass  Filter by subclass or options like ['backedEnum' => true]
     */
    public function signatureParameters(string|array|null $subClass = null): array
    {
        $controller = $this->getAction('uses');

        if (! $controller) {
            return [];
        }

        try {
            if (is_string($controller) && str_contains($controller, '::')) {
                [$class, $method] = explode('::', $controller, 2);
                $reflection = new \ReflectionMethod($class, $method);
            } elseif (is_array($controller)) {
                [$class, $method] = $controller;
                $reflection = new \ReflectionMethod($class, $method);
            } elseif (is_string($controller) && class_exists($controller)) {
                $reflection = new \ReflectionMethod($controller, '__invoke');
            } elseif ($controller instanceof Closure) {
                $reflection = new \ReflectionFunction($controller);
            } else {
                return [];
            }

            $parameters = $reflection->getParameters();

            // Apply filtering if requested
            if ($subClass === null) {
                return $parameters;
            }

            // Handle backedEnum filtering
            if (is_array($subClass) && isset($subClass['backedEnum']) && $subClass['backedEnum']) {
                return array_filter($parameters, function ($param) {
                    $type = $param->getType();
                    if (! $type instanceof \ReflectionNamedType) {
                        return false;
                    }
                    $typeName = $type->getName();
                    if (! enum_exists($typeName)) {
                        return false;
                    }
                    $enumReflection = new \ReflectionEnum($typeName);

                    return $enumReflection->isBacked();
                });
            }

            // Handle subclass filtering (e.g., UrlRoutable)
            if (is_string($subClass)) {
                return array_filter($parameters, function ($param) use ($subClass) {
                    $type = $param->getType();
                    if (! $type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                        return false;
                    }
                    $typeName = $type->getName();

                    return is_a($typeName, $subClass, true);
                });
            }

            return $parameters;
        } catch (\ReflectionException) {
            return [];
        }
    }

    /**
     * Get the middleware attached to the route.
     *
     * @return array<int, string>
     */
    public function middleware(): array
    {
        return $this->getAction('middleware') ?? [];
    }

    /**
     * Check if route has a given name.
     */
    public function named(string $name): bool
    {
        return $this->getName() === $name;
    }

    /**
     * Get all route defaults/parameters.
     */
    public function defaults(): array
    {
        return $this->route->getDefaults();
    }

    /**
     * Magic method to access route properties.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'uri' => $this->uri(),
            'methods' => $this->methods(),
            default => null,
        };
    }
}
