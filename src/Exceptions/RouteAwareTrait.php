<?php

namespace Dedoc\Scramble\Exceptions;

use Dedoc\Scramble\Support\RouteAdapter;
use Exception;

/**
 * @mixin Exception
 */
trait RouteAwareTrait
{
    protected ?RouteAdapter $route = null;

    public function setRoute(RouteAdapter $route): static
    {
        $this->route = $route;

        if (method_exists($this, 'getRouteAwareMessage')) {
            $this->message = $this->getRouteAwareMessage($route, $this->getMessage());
        }

        return $this;
    }

    public function getRoute(): ?RouteAdapter
    {
        return $this->route;
    }
}
