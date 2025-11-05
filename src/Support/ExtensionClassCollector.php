<?php

namespace Dedoc\Scramble\Support;

use Dedoc\Scramble\Extensions\ExceptionToResponseExtension;
use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

    public function __construct(
        private ContainerInterface $container
    ) {
        $this->collectExtensionClasses();
    }

    private function collectExtensionClasses(): void
    {
        // Get all registered service IDs
        $serviceIds = $this->container->getServiceIds();

        foreach ($serviceIds as $serviceId) {
            // Skip private services and internal Symfony services
            if (str_starts_with($serviceId, '.') || str_starts_with($serviceId, 'Symfony\\')) {
                continue;
            }

            try {
                // Try to get service definition to check its class
                $definition = $this->container->get($serviceId);
                $class = get_class($definition);

                if (is_a($class, TypeToSchemaExtension::class, true)) {
                    $this->typeToSchemaExtensionClasses[] = $class;
                }

                if (is_a($class, ExceptionToResponseExtension::class, true)) {
                    $this->exceptionToResponseExtensionClasses[] = $class;
                }
            } catch (\Exception $e) {
                // Skip services that can't be instantiated
                continue;
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
