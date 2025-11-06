<?php

namespace Dedoc\Scramble;

use Dedoc\Scramble\Configuration\OperationTransformers;
use Dedoc\Scramble\DependencyInjection\Compiler\ScrambleExtensionPass;
use Dedoc\Scramble\DocumentTransformers\AddDocumentTags;
use Dedoc\Scramble\DocumentTransformers\CleanupUnusedResponseReferencesTransformer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Routing\Route;

class ScrambleBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Register compiler pass for extension discovery
        $container->addCompilerPass(new ScrambleExtensionPass);
    }

    public function boot(): void
    {
        parent::boot();

        // Set global container reference for app() helper function
        // This allows legacy code to access the service container
        $GLOBALS['__scramble_container'] = $this->container;

        // Initialize Infer Context with required dependencies
        $this->configureInferContext();

        // Initialize Scramble configuration
        $this->configureScramble();

        // Register documentation routes dynamically
        $this->registerDocumentationRoutes();
    }

    /**
     * Configure Infer Context with dependencies from container.
     */
    private function configureInferContext(): void
    {
        $extensionsBroker = $this->container->get(Infer\Extensions\ExtensionsBroker::class);
        $shallowIndex = $this->container->get(Infer\Scope\LazyShallowReflectionIndex::class);

        Infer\Context::configure($extensionsBroker, $shallowIndex);
    }

    /**
     * Configure Scramble with operation and document transformers.
     */
    private function configureScramble(): void
    {
        $config = $this->container->getParameter('scramble');

        Scramble::configure()
            ->useConfig($config)
            ->withOperationTransformers(function (OperationTransformers $transformers) {
                // Get operation extensions from container
                // Extensions are automatically tagged and collected
                $operationExtensions = [];
                if ($this->container->has('scramble.operation_extensions')) {
                    $operationExtensions = $this->container->get('scramble.operation_extensions');
                }

                $transformers->append($operationExtensions);
            })
            ->withDocumentTransformers([
                AddDocumentTags::class,
                CleanupUnusedResponseReferencesTransformer::class,
            ]);

        // Handle default routes ignored configuration
        if (Scramble::$defaultRoutesIgnored) {
            Scramble::configure()->expose(false);
        }
    }

    /**
     * Register documentation routes for UI and JSON spec endpoints.
     *
     * Routes are registered dynamically based on GeneratorConfig settings.
     */
    private function registerDocumentationRoutes(): void
    {
        // Documentation routes are now registered via the routing system
        // See config/routes.yaml for route definitions
        // Dynamic route registration based on GeneratorConfig will be handled
        // by a route loader or controller configuration
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
