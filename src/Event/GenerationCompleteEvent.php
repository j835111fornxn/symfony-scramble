<?php

namespace Dedoc\Scramble\Event;

use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched after OpenAPI documentation generation completes.
 *
 * This event allows extension developers to post-process the generated
 * OpenAPI specification or perform cleanup actions.
 */
class GenerationCompleteEvent extends Event
{
    public const NAME = 'scramble.generation.complete';

    public function __construct(
        private OpenApi $openApi,
        private GeneratorConfig $config,
    ) {}

    public function getOpenApi(): OpenApi
    {
        return $this->openApi;
    }

    public function setOpenApi(OpenApi $openApi): void
    {
        $this->openApi = $openApi;
    }

    public function getConfig(): GeneratorConfig
    {
        return $this->config;
    }
}
