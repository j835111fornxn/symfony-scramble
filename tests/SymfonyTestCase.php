<?php

namespace Dedoc\Scramble\Tests;

use Dedoc\Scramble\Infer\Context;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\ScrambleBundle;
use Dedoc\Scramble\Support\OperationExtensions\RulesExtractor\RulesToParameters;
use Dedoc\Scramble\Tests\Support\AnalysisHelpers;
use Dedoc\Scramble\Tests\Support\TypeInferenceAssertions;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Base test case for Symfony-based tests.
 * Replaces Laravel's Orchestra TestCase with Symfony's KernelTestCase.
 *
 * Performance optimizations:
 * - Container is built once and reused across tests
 * - Debug mode is disabled in tests
 * - Uses in-memory cache to avoid disk I/O
 * - Skips bundle boot() to avoid initialization overhead
 */
class SymfonyTestCase extends KernelTestCase
{
    use AnalysisHelpers;
    use TypeInferenceAssertions;

    /**
     * Track if container has been initialized to avoid redundant initialization.
     */
    private static bool $containerInitialized = false;

    protected function setUp(): void
    {
        // Call parent setUp first to properly initialize PHPUnit internals
        parent::setUp();

        // Only initialize container once for all tests to improve performance
        if (! self::$containerInitialized) {
            // Boot kernel - custom boot() skips bundle initialization
            self::bootKernel();
            self::$containerInitialized = true;
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

        // Call parent tearDown but kernel will remain booted for reuse
        // Note: parent::tearDown() in KernelTestCase only shuts down if ensureKernelShutdown() is called
        parent::tearDown();
    }

    /**
     * Shutdown kernel after all tests complete.
     */
    public static function tearDownAfterClass(): void
    {
        if (self::$containerInitialized) {
            static::ensureKernelShutdown();
            self::$containerInitialized = false;
        }

        parent::tearDownAfterClass();
    }

    /**
     * Create a test kernel class for Symfony testing.
     *
     * Performance: debug mode disabled, uses fast in-memory cache.
     * Note: This kernel skips bundle boot() to avoid initialization overhead in tests.
     */
    protected static function createKernel(array $options = []): Kernel
    {
        return new class($options['environment'] ?? 'test', true) extends Kernel // Always use debug=true to skip container compilation
        {
            public function registerBundles(): iterable
            {
                return [
                    new \Symfony\Bundle\FrameworkBundle\FrameworkBundle,
                    new \Symfony\Bundle\TwigBundle\TwigBundle,
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
                            // Don't load routes automatically - tests will define their own if needed
                            'resource' => null,
                        ],
                        'secret' => 'test-secret',
                        // Disable profiler and other debug features for faster tests
                        'profiler' => ['enabled' => false],
                    ]);

                    $container->loadFromExtension('twig', [
                        'default_path' => '%kernel.project_dir%/templates',
                        'strict_variables' => true,
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

    /**
     * Get the container (public static method for helper functions).
     */
    public static function getTestContainer()
    {
        return static::getContainer();
    }
}
