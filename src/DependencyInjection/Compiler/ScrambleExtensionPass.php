<?php

namespace Dedoc\Scramble\DependencyInjection\Compiler;

use Dedoc\Scramble\Extensions\ExceptionToResponseExtension;
use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Infer\Extensions\InferExtension;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ScrambleExtensionPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Collect all extensions tagged with scramble extension tags
        $this->collectExtensions($container, 'scramble.infer_extension', InferExtension::class);
        $this->collectExtensions($container, 'scramble.type_to_schema_extension', TypeToSchemaExtension::class);
        $this->collectExtensions($container, 'scramble.operation_extension', OperationExtension::class);
        $this->collectExtensions($container, 'scramble.exception_to_response_extension', ExceptionToResponseExtension::class);
    }

    private function collectExtensions(ContainerBuilder $container, string $tag, string $interface): void
    {
        $taggedServices = $container->findTaggedServiceIds($tag);

        foreach ($taggedServices as $id => $tags) {
            $def = $container->getDefinition($id);
            $class = $def->getClass();

            if (! is_a($class, $interface, true)) {
                throw new \InvalidArgumentException(
                    sprintf('Service "%s" must implement "%s".', $id, $interface)
                );
            }
        }

        // Extensions are injected via tagged_iterator in services.yaml
        // No need to store as container parameters which cannot contain service references
    }
}
