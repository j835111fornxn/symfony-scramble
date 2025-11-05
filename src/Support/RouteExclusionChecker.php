<?php

namespace Dedoc\Scramble\Support;

use Dedoc\Scramble\Attributes\ExcludeAllRoutesFromDocs;
use Dedoc\Scramble\Attributes\ExcludeRouteFromDocs;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * Helper class to check if routes should be excluded from documentation.
 */
class RouteExclusionChecker
{
    /**
     * Check if a route should be excluded based on attributes.
     */
    public function shouldExclude(RouteAdapter $route): bool
    {
        $controller = $route->getAction('uses');

        if (! $controller || ! is_string($controller)) {
            return false;
        }

        try {
            if (str_contains($controller, '::')) {
                [$class, $method] = explode('::', $controller, 2);
            } else {
                $class = $controller;
                $method = '__invoke';
            }

            // Check class-level exclusion
            $classReflection = new ReflectionClass($class);
            $classAttributes = $classReflection->getAttributes(ExcludeAllRoutesFromDocs::class);

            if (! empty($classAttributes)) {
                return true;
            }

            // Check method-level exclusion
            if (method_exists($class, $method)) {
                $methodReflection = new ReflectionMethod($class, $method);
                $methodAttributes = $methodReflection->getAttributes(ExcludeRouteFromDocs::class);

                if (! empty($methodAttributes)) {
                    return true;
                }
            }
        } catch (ReflectionException) {
            // If reflection fails, don't exclude
            return false;
        }

        return false;
    }

    /**
     * Check if a route is marked as @only-docs.
     */
    public function isOnlyDocs(RouteAdapter $route): bool
    {
        $controller = $route->getAction('uses');

        if (! $controller || ! is_string($controller)) {
            return false;
        }

        try {
            if (str_contains($controller, '::')) {
                [$class, $method] = explode('::', $controller, 2);
            } else {
                $class = $controller;
                $method = '__invoke';
            }

            if (method_exists($class, $method)) {
                $reflection = new ReflectionMethod($class, $method);
                $docComment = $reflection->getDocComment();

                return $docComment && str_contains($docComment, '@only-docs');
            }
        } catch (ReflectionException) {
            // If reflection fails, return false
        }

        return false;
    }
}
