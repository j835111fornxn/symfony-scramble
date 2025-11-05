<?php

namespace Dedoc\Scramble\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ScrambleExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Load service definitions
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );
        $loader->load('services.yaml');

                // Set config as container parameters
        $container->setParameter('scramble.api_path', $config['api_path']);
        $container->setParameter('scramble.api_domain', $config['api_domain']);
        $container->setParameter('scramble.export_path', $config['export_path']);
        $container->setParameter('scramble.info', $config['info']);
        $container->setParameter('scramble.ui', $config['ui']);
        $container->setParameter('scramble.servers', $config['servers']);
        $container->setParameter('scramble.enum_cases_description_strategy', $config['enum_cases_description_strategy']);
        $container->setParameter('scramble.enum_cases_names_strategy', $config['enum_cases_names_strategy']);
        $container->setParameter('scramble.flatten_deep_query_parameters', $config['flatten_deep_query_parameters']);
        $container->setParameter('scramble.middleware', $config['middleware']);
        $container->setParameter('scramble.extensions', $config['extensions']);
    }

    public function getAlias(): string
    {
        return 'scramble';
    }
}
