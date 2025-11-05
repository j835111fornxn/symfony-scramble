<?php

namespace Dedoc\Scramble\Event;

use Dedoc\Scramble\GeneratorConfig;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched before OpenAPI documentation generation starts.
 *
 * This event allows extension developers to perform actions or modify
 * configuration before routes are analyzed and documentation is generated.
 */
class GenerationStartEvent extends Event
{
    public const NAME = 'scramble.generation.start';

    public function __construct(
        private GeneratorConfig $config,
    ) {}

    public function getConfig(): GeneratorConfig
    {
        return $this->config;
    }

    public function setConfig(GeneratorConfig $config): void
    {
        $this->config = $config;
    }
}
