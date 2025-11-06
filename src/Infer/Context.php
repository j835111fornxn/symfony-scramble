<?php

namespace Dedoc\Scramble\Infer;

use Dedoc\Scramble\Infer\Extensions\ExtensionsBroker;
use Dedoc\Scramble\Infer\Scope\LazyShallowReflectionIndex;

class Context
{
    private static $instance = null;

    public function __construct(
        public readonly ExtensionsBroker $extensionsBroker,
        public readonly LazyShallowReflectionIndex $shallowIndex,
    ) {}

    public static function configure(
        ExtensionsBroker $extensionsBroker,
        LazyShallowReflectionIndex $shallowIndex,
    ) {
        static::$instance = new static(
            $extensionsBroker,
            $shallowIndex,
        );
    }

    public static function getInstance(): static
    {
        if (! static::$instance) {
            throw new \RuntimeException('Context not configured. Call Context::configure() first.');
        }

        return static::$instance;
    }

    public static function reset()
    {
        static::$instance = null;
    }
}
