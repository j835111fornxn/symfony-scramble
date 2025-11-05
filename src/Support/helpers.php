<?php

/**
 * Symfony-compatible helper functions to replace Laravel helpers.
 * This file provides backward compatibility during the migration.
 */

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as SymfonyContainerInterface;

if (!function_exists('app')) {
    /**
     * Get an instance from the service container.
     *
     * @param string|null $abstract
     * @param array $parameters
     * @return mixed|ContainerInterface
     */
    function app(?string $abstract = null, array $parameters = [])
    {
        static $container = null;

        if ($container === null) {
            // Get container from global state set by ScrambleBundle
            $container = $GLOBALS['__scramble_container'] ?? null;

            if ($container === null) {
                throw new RuntimeException('Service container not initialized. ScrambleBundle may not be loaded.');
            }
        }

        if ($abstract === null) {
            return $container;
        }

        if (!empty($parameters) && $container instanceof SymfonyContainerInterface) {
            // Symfony doesn't support parameters in get() the same way Laravel does
            // We need to use a factory or manually inject parameters
            throw new RuntimeException('Parameters are not yet supported in app() helper. Use constructor injection instead.');
        }

        return $container->get($abstract);
    }
}

if (!function_exists('collect')) {
    /**
     * Create a collection from the given value.
     * Returns an array wrapper that mimics Laravel Collection methods.
     *
     * @param mixed $value
     * @return \Dedoc\Scramble\Support\Collection
     */
    function collect($value = [])
    {
        return new \Dedoc\Scramble\Support\Collection($value);
    }
}

if (!function_exists('config')) {
    /**
     * Get / set the specified configuration value.
     *
     * @param array|string|null $key
     * @param mixed $default
     * @return mixed
     */
    function config($key = null, $default = null)
    {
        static $parameterBag = null;

        if ($parameterBag === null) {
            $container = app();
            if (method_exists($container, 'getParameterBag')) {
                $parameterBag = $container->getParameterBag();
            } else {
                throw new RuntimeException('Cannot access configuration. Container does not support getParameterBag().');
            }
        }

        if ($key === null) {
            return $parameterBag->all();
        }

        if (is_array($key)) {
            throw new RuntimeException('Setting configuration at runtime is not supported in Symfony.');
        }

        // Convert Laravel dot notation to Symfony parameter names
        // e.g., 'app.name' -> 'kernel.project_dir' or custom parameters
        $parameterName = str_replace('.', '_', $key);

        return $parameterBag->has($parameterName)
            ? $parameterBag->get($parameterName)
            : $default;
    }
}

if (!function_exists('logger')) {
    /**
     * Log a debug message to the logs.
     *
     * @param string|null $message
     * @param array $context
     * @return \Psr\Log\LoggerInterface|void
     */
    function logger(?string $message = null, array $context = [])
    {
        /** @var \Psr\Log\LoggerInterface $logger */
        $logger = app('logger');

        if ($message === null) {
            return $logger;
        }

        $logger->debug($message, $context);
    }
}

if (!function_exists('url')) {
    /**
     * Generate a URL for the application.
     *
     * @param string|null $path
     * @param mixed $parameters
     * @param bool|null $secure
     * @return string|\Symfony\Component\Routing\Generator\UrlGeneratorInterface
     */
    function url(?string $path = null, $parameters = [], ?bool $secure = null)
    {
        /** @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface $generator */
        $generator = app('router.default');

        if ($path === null) {
            return $generator;
        }

        // For simple paths (not route names), generate absolute URL
        // This is a simplified implementation
        if (str_starts_with($path, '/')) {
            // Get request context for protocol/host
            $context = $generator->getContext();
            $scheme = $secure === true ? 'https' : ($secure === false ? 'http' : $context->getScheme());
            $host = $context->getHost();
            $baseUrl = $context->getBaseUrl();

            return $scheme . '://' . $host . $baseUrl . $path;
        }

        // Assume it's a route name
        return $generator->generate($path, $parameters, $secure ? 1 : 0);
    }
}
