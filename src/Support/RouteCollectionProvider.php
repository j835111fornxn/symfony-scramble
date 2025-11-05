<?php

namespace Dedoc\Scramble\Support;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * Provides filtered route collection based on configuration.
 */
class RouteCollectionProvider
{
    public function __construct(
        private RouterInterface $router,
        private array $config = []
    ) {}

    /**
     * Get filtered routes based on api_path and api_domain configuration.
     */
    public function getRoutes(): RouteCollection
    {
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

        return $filteredRoutes;
    }

    /**
     * Check if a route should be included based on path and domain filters.
     */
    private function shouldIncludeRoute(Route $route, string $apiPath, ?string $apiDomain): bool
    {
        // Check path prefix
        $path = $route->getPath();
        if ($apiPath && ! str_starts_with(ltrim($path, '/'), $apiPath)) {
            return false;
        }

        // Check domain
        if ($apiDomain && $route->getHost() !== $apiDomain) {
            return false;
        }

        return true;
    }
}
