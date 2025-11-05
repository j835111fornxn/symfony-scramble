<?php

namespace Dedoc\Scramble;

use Dedoc\Scramble\DependencyInjection\Compiler\ScrambleExtensionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

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

        // Route registration will be handled by the configuration
        // Routes are registered dynamically based on bundle configuration
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
