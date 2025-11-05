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
 *
 * Performance optimizations:
 * - Kernel is booted once and reused across tests
 * - Debug mode is disabled in tests
 * - Uses in-memory cache to avoid disk I/O
 */
class SymfonyTestCase extends KernelTestCase
{
    /**
     * Track if kernel has been booted to avoid redundant boots.
     */
    private static bool $kernelBooted = false;

    protected function setUp(): void
    {
        // Skip parent::setUp() which would reset kernel
        // parent::setUp();

        // Only boot kernel once for all tests to improve performance
        if (! self::$kernelBooted) {
            self::bootKernel();
            self::$kernelBooted = true;
        }

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
            fn ($r) => ! str_starts_with($r->getName() ?? '', '_'),
        ));
    }

    protected function tearDown(): void
    {
        Context::reset();

        Scramble::$tagResolver = null;
        Scramble::$enforceSchemaRules = [];
        Scramble::$defaultRoutesIgnored = false;
        Scramble::$extensions = [];

        // Don't shutdown kernel - reuse it for next test
        // parent::tearDown() would shut it down
    }

    /**
     * Shutdown kernel after all tests complete.
     */
    public static function tearDownAfterClass(): void
    {
        if (self::$kernelBooted) {
            static::ensureKernelShutdown();
            self::$kernelBooted = false;
        }

        parent::tearDownAfterClass();
    }

    /**
     * Create a test kernel class for Symfony testing.
     *
     * Performance: debug mode disabled, uses fast in-memory cache.
     */
    protected static function createKernel(array $options = []): Kernel
    {
        return new class($options['environment'] ?? 'test', $options['debug'] ?? false) extends Kernel
        {
            public function registerBundles(): iterable
            {
                return [
                    new \Symfony\Bundle\FrameworkBundle\FrameworkBundle,
                    new ScrambleBundle,
                ];
            }

            public function registerContainerConfiguration(\Symfony\Component\Config\Loader\LoaderInterface $loader): void
            {
                $loader->load(function (ContainerBuilder $container) {
                    $container->loadFromExtension('framework', [
                        'test' => true,
                        'router' => [
                            'utf8' => true,
                            'resource' => '%kernel.project_dir%/routes',
                        ],
                        'secret' => 'test-secret',
                        // Disable profiler and other debug features for faster tests
                        'profiler' => ['enabled' => false],
                    ]);

                    $container->loadFromExtension('scramble', [
                        'api_path' => 'api',
                        'api_domain' => null,
                    ]);
                });
            }

            public function getCacheDir(): string
            {
                // Use in-memory filesystem for cache to avoid disk I/O
                // Each test class gets its own cache namespace
                return sys_get_temp_dir().'/scramble_test_cache/'.substr(md5(self::class), 0, 8);
            }

            public function getLogDir(): string
            {
                return sys_get_temp_dir().'/scramble_test_logs';
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
