<?php

namespace Dedoc\Scramble\Tests;

use Dedoc\Scramble\Infer\Context;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\ScrambleBundle;
use Dedoc\Scramble\Support\OperationExtensions\RulesExtractor\RulesToParameters;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Base test case for Symfony-based tests.
 * Replaces Laravel's Orchestra TestCase with Symfony's KernelTestCase.
 */
class SymfonyTestCase extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Boot the Symfony kernel
        self::bootKernel();

        Scramble::throwOnError();

        // Configure RulesToParameters service if needed
        $container = static::getContainer();
        if ($container->has(RulesToParameters::class)) {
            $rtp = $container->get(RulesToParameters::class);
            // Configure as needed
        }
    }

    /**
     * Get all routes from the Symfony router.
     */
    protected function getScrambleRoutes(): array
    {
        $router = static::getContainer()->get('router');
        $routes = $router->getRouteCollection()->all();

        return array_values(array_filter(
            $routes,
            fn($r) => !str_starts_with($r->getName() ?? '', '_'),
        ));
    }

    protected function tearDown(): void
    {
        Context::reset();

        Scramble::$tagResolver = null;
        Scramble::$enforceSchemaRules = [];
        Scramble::$defaultRoutesIgnored = false;
        Scramble::$extensions = [];

        parent::tearDown();
    }

    /**
     * Create a test kernel class for Symfony testing.
     */
    protected static function createKernel(array $options = []): Kernel
    {
        return new class($options['environment'] ?? 'test', $options['debug'] ?? true) extends Kernel {
            public function registerBundles(): iterable
            {
                return [
                    new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
                    new ScrambleBundle(),
                ];
            }

            public function registerContainerConfiguration(\Symfony\Component\Config\Loader\LoaderInterface $loader): void
            {
                $loader->load(function (ContainerBuilder $container) {
                    $container->loadFromExtension('framework', [
                        'test' => true,
                        'router' => ['utf8' => true],
                        'secret' => 'test-secret',
                    ]);

                    $container->loadFromExtension('scramble', [
                        'api_path' => 'api',
                        'api_domain' => null,
                    ]);
                });
            }

            public function getCacheDir(): string
            {
                return sys_get_temp_dir() . '/scramble_test_cache';
            }

            public function getLogDir(): string
            {
                return sys_get_temp_dir() . '/scramble_test_logs';
            }
        };
    }

    /**
     * Get a service from the container.
     */
    protected function get(string $id)
    {
        return static::getContainer()->get($id);
    }
}
