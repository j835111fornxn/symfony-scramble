<?php

namespace Dedoc\Scramble\Support;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * Service to manage Symfony routes for documentation generation.
 */
class SymfonyRouteManager
{
    private ?RouteCollection $cachedRoutes = null;

    public function __construct(
        private RouterInterface $router,
        private array $config = []
    ) {}

    /**
     * Get all routes that should be documented based on configuration.
     *
     * @return RouteAdapter[]
     */
    public function getDocumentedRoutes(): array
    {
        $routes = $this->getFilteredRoutes();
        $adapters = [];

        /** @var Route $route */
        foreach ($routes as $name => $route) {
            $adapters[] = new RouteAdapter($route, $name);
        }

        return $adapters;
    }

    /**
     * Get filtered route collection based on api_path and api_domain.
     */
    private function getFilteredRoutes(): RouteCollection
    {
        if ($this->cachedRoutes !== null) {
            return $this->cachedRoutes;
        }

        $allRoutes = $this->router->getRouteCollection();
        $filteredRoutes = new RouteCollection;

        $apiPath = $this->config['api_path'] ?? 'api';
        $apiDomain = $this->config['api_domain'] ?? null;

        /** @var Route $route */
        foreach ($allRoutes as $name => $route) {
            if ($this->shouldIncludeRoute($route, $apiPath, $apiDomain)) {
                $filteredRoutes->add($name, $route);
            }
        }

        return $this->cachedRoutes = $filteredRoutes;
    }

    /**
     * Check if a route should be included in documentation.
     */
    private function shouldIncludeRoute(Route $route, string $apiPath, ?string $apiDomain): bool
    {
        // Skip routes without controllers
        $defaults = $route->getDefaults();
        if (! isset($defaults['_controller'])) {
            return false;
        }

        // Check path prefix
        $path = ltrim($route->getPath(), '/');
        if ($apiPath && ! str_starts_with($path, $apiPath)) {
            return false;
        }

        // Check domain if specified
        if ($apiDomain !== null) {
            $host = $route->getHost();
            if ($host && $host !== $apiDomain) {
                return false;
            }
        }

        // Skip internal Symfony routes (starting with _)
        if (str_starts_with($path, '_')) {
            return false;
        }

        return true;
    }

    /**
     * Clear cached routes.
     */
    public function clearCache(): void
    {
        $this->cachedRoutes = null;
    }

    /**
     * Get route by name.
     */
    public function getRoute(string $name): ?RouteAdapter
    {
        $routes = $this->getFilteredRoutes();
        $route = $routes->get($name);

        return $route ? new RouteAdapter($route, $name) : null;
    }

    /**
     * Check if a route exists.
     */
    public function hasRoute(string $name): bool
    {
        return $this->getFilteredRoutes()->get($name) !== null;
    }

    /**
     * Get total count of documented routes.
     */
    public function count(): int
    {
        return $this->getFilteredRoutes()->count();
    }
}
