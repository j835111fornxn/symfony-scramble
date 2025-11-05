<?php

namespace Dedoc\Scramble\Event;

use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\RouteAdapter;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when an OpenAPI operation is generated from a route.
 *
 * This event allows extension developers to modify or enhance individual
 * operations during documentation generation.
 */
class OperationGeneratedEvent extends Event
{
    public const NAME = 'scramble.operation.generated';

    public function __construct(
        private Operation $operation,
        private RouteAdapter $route,
        private GeneratorConfig $config,
    ) {}

    public function getOperation(): Operation
    {
        return $this->operation;
    }

    public function setOperation(Operation $operation): void
    {
        $this->operation = $operation;
    }

    public function getRoute(): RouteAdapter
    {
        return $this->route;
    }

    public function getConfig(): GeneratorConfig
    {
        return $this->config;
    }
}
