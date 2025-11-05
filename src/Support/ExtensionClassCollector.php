<?php

namespace Dedoc\Scramble\Support;

use Dedoc\Scramble\Extensions\ExceptionToResponseExtension;
use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Collects extension class names for TypeTransformer instantiation.
 *
 * TypeTransformer needs class names (not instances) because it instantiates
 * extensions with specific parameters (infer, transformer, components, context).
 */
class ExtensionClassCollector
{
    /** @var array<string> */
    private array $typeToSchemaExtensionClasses = [];

    /** @var array<string> */
    private array $exceptionToResponseExtensionClasses = [];

    /** @var array<string> Tagged service IDs */
    private array $taggedServiceIds = [];

    public function __construct(
        private ContainerInterface $container,
        array $taggedServices = []
    ) {
        $this->taggedServiceIds = $taggedServices;
        $this->collectExtensionClasses();
    }

    private function collectExtensionClasses(): void
    {
        // If tagged services are provided (from compiler pass), use them
        if (! empty($this->taggedServiceIds)) {
            $this->collectFromTaggedServices();

            return;
        }

        // Fallback: try to discover services manually
        // This is a backup strategy when tag collection isn't available
        $this->collectFromKnownNamespaces();
    }

    private function collectFromTaggedServices(): void
    {
        foreach ($this->taggedServiceIds as $serviceId) {
            try {
                if (! $this->container->has($serviceId)) {
                    continue;
                }

                $service = $this->container->get($serviceId);
                $class = get_class($service);

                if (is_a($class, TypeToSchemaExtension::class, true)) {
                    $this->typeToSchemaExtensionClasses[] = $class;
                }

                if (is_a($class, ExceptionToResponseExtension::class, true)) {
                    $this->exceptionToResponseExtensionClasses[] = $class;
                }
            } catch (ServiceNotFoundException $e) {
                // Skip services that don't exist
                continue;
            }
        }

        // Remove duplicates
        $this->typeToSchemaExtensionClasses = array_unique($this->typeToSchemaExtensionClasses);
        $this->exceptionToResponseExtensionClasses = array_unique($this->exceptionToResponseExtensionClasses);
    }

    private function collectFromKnownNamespaces(): void
    {
        // Manually register built-in extensions
        // This is a fallback when service tagging is not available
        $knownExtensions = [
            // TypeToSchema extensions
            'Dedoc\Scramble\Support\TypeToSchemaExtensions\ModelToSchema',
            'Dedoc\Scramble\Support\TypeToSchemaExtensions\DoctrineEntityToSchema',
            'Dedoc\Scramble\Support\TypeToSchemaExtensions\CollectionToSchema',
            'Dedoc\Scramble\Support\TypeToSchemaExtensions\EnumToSchema',
            'Dedoc\Scramble\Support\TypeToSchemaExtensions\StreamedResponseToSchema',
            'Dedoc\Scramble\Support\TypeToSchemaExtensions\ResponseTypeToSchema',
            'Dedoc\Scramble\Support\TypeToSchemaExtensions\BinaryFileResponseToSchema',
            'Dedoc\Scramble\Support\TypeToSchemaExtensions\VoidTypeToSchema',

            // ExceptionToResponse extensions
            'Dedoc\Scramble\Support\ExceptionToResponseExtensions\ValidationExceptionToResponseExtension',
            'Dedoc\Scramble\Support\ExceptionToResponseExtensions\NotFoundExceptionToResponseExtension',
            'Dedoc\Scramble\Support\ExceptionToResponseExtensions\AuthenticationExceptionToResponseExtension',
            'Dedoc\Scramble\Support\ExceptionToResponseExtensions\AuthorizationExceptionToResponseExtension',
            'Dedoc\Scramble\Support\ExceptionToResponseExtensions\HttpExceptionToResponseExtension',
        ];

        foreach ($knownExtensions as $class) {
            if (! class_exists($class)) {
                continue;
            }

            if (is_a($class, TypeToSchemaExtension::class, true)) {
                $this->typeToSchemaExtensionClasses[] = $class;
            }

            if (is_a($class, ExceptionToResponseExtension::class, true)) {
                $this->exceptionToResponseExtensionClasses[] = $class;
            }
        }

        // Remove duplicates
        $this->typeToSchemaExtensionClasses = array_unique($this->typeToSchemaExtensionClasses);
        $this->exceptionToResponseExtensionClasses = array_unique($this->exceptionToResponseExtensionClasses);
    }

    /**
     * @return array<string>
     */
    public function getTypeToSchemaExtensionClasses(): array
    {
        return $this->typeToSchemaExtensionClasses;
    }

    /**
     * @return array<string>
     */
    public function getExceptionToResponseExtensionClasses(): array
    {
        return $this->exceptionToResponseExtensionClasses;
    }
}
