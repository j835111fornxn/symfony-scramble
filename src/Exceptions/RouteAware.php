<?php

namespace Dedoc\Scramble\Exceptions;

use Dedoc\Scramble\Support\RouteAdapter;

interface RouteAware
{
    public function setRoute(RouteAdapter $route): static;

    public function getRoute(): ?RouteAdapter;

    public function getRouteAwareMessage(RouteAdapter $route, string $msg): string;
}
