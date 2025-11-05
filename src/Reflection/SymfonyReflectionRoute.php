<?php

namespace Dedoc\Scramble\Reflection;

use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use Symfony\Component\Routing\Route;
use WeakMap;

/**
 * Reflection helper for Symfony routes.
 *
 * @internal
 */
class SymfonyReflectionRoute
{
    private static WeakMap $cache;

    private function __construct(private Route $route) {}

    public static function createFromRoute(Route $route): static
    {
        static::$cache ??= new WeakMap;

        return static::$cache[$route] ??= new static($route);
    }

    /**
     * Get mapping of route parameter names to controller method parameter names.
     *
     * For example:
     * Route: /users/{user_id}/posts/{post_id}
     * Controller method: public function show(int $userId, int $postId)
     * Result: ['user_id' => 'userId', 'post_id' => 'postId']
     *
     * @return array<string, string>
     */
    public function getSignatureParametersMap(): array
    {
        $routeParams = $this->getRouteParameterNames();
        $controllerParams = $this->getControllerParameters();

        if (empty($routeParams) || empty($controllerParams)) {
            return [];
        }

        $map = [];

        foreach ($routeParams as $routeParam) {
            // Try to find matching controller parameter
            // Convert snake_case to camelCase for matching
            $camelCaseParam = $this->snakeToCamel($routeParam);

            foreach ($controllerParams as $controllerParam) {
                if (
                    $controllerParam->getName() === $camelCaseParam ||
                    $controllerParam->getName() === $routeParam
                ) {
                    $map[$routeParam] = $controllerParam->getName();
                    break;
                }
            }

            // If no match found, use snake_case name
            if (! isset($map[$routeParam])) {
                $map[$routeParam] = $routeParam;
            }
        }

        return $map;
    }

    /**
     * Get route parameter names from path.
     *
     * @return array<int, string>
     */
    private function getRouteParameterNames(): array
    {
        $path = $this->route->getPath();
        preg_match_all('/\{(\w+)\}/', $path, $matches);

        return $matches[1] ?? [];
    }

    /**
     * Get controller method parameters.
     *
     * @return ReflectionParameter[]
     */
    private function getControllerParameters(): array
    {
        $defaults = $this->route->getDefaults();
        $controller = $defaults['_controller'] ?? null;

        if (! $controller) {
            return [];
        }

        try {
            // Handle different controller formats
            if (is_string($controller) && str_contains($controller, '::')) {
                [$class, $method] = explode('::', $controller, 2);
                $reflection = new ReflectionMethod($class, $method);
            } elseif (is_array($controller)) {
                [$class, $method] = $controller;
                $reflection = new ReflectionMethod($class, $method);
            } elseif (is_string($controller) && class_exists($controller)) {
                // Invokable controller
                $reflection = new ReflectionMethod($controller, '__invoke');
            } elseif (is_callable($controller)) {
                $reflection = new ReflectionFunction($controller);
            } else {
                return [];
            }

            return $reflection->getParameters();
        } catch (ReflectionException) {
            return [];
        }
    }

    /**
     * Convert snake_case to camelCase.
     */
    private function snakeToCamel(string $value): string
    {
        return lcfirst(str_replace('_', '', ucwords($value, '_')));
    }

    /**
     * Get controller class and method name.
     *
     * @return array{class: string|null, method: string|null}
     */
    public function getControllerInfo(): array
    {
        $defaults = $this->route->getDefaults();
        $controller = $defaults['_controller'] ?? null;

        if (! $controller) {
            return ['class' => null, 'method' => null];
        }

        if (is_string($controller) && str_contains($controller, '::')) {
            [$class, $method] = explode('::', $controller, 2);

            return ['class' => $class, 'method' => $method];
        }

        if (is_array($controller)) {
            [$class, $method] = $controller;

            return ['class' => is_string($class) ? $class : get_class($class), 'method' => $method];
        }

        if (is_string($controller) && class_exists($controller)) {
            return ['class' => $controller, 'method' => '__invoke'];
        }

        return ['class' => null, 'method' => null];
    }

    /**
     * Get bound parameter types based on route requirements.
     *
     * @return array<string, string>
     */
    public function getBoundParametersTypes(): array
    {
        $requirements = $this->route->getRequirements();
        $types = [];

        foreach ($this->getRouteParameterNames() as $param) {
            // Try to infer type from requirement regex
            $requirement = $requirements[$param] ?? null;

            if ($requirement === '\d+') {
                $types[$param] = 'int';
            } else {
                $types[$param] = 'string';
            }
        }

        return $types;
    }
}
