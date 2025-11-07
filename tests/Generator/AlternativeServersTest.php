<?php

namespace Dedoc\Scramble\Tests\Generator;

use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Routing\Route;

final class AlternativeServersTest extends SymfonyTestCase
{
    #[Test]
    public function addsAnAlternativeServerToOperationWhenNoMatchingServerFound(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            // Register routes via Symfony router
            $router = $this->get('router');
            $router->add('post-test', new Route('/api/test', [], [], [], null, [], ['POST']));
            $router->add('domain-get-test', new Route('/api/test', [], [], [], '{param}.localhost', [], ['GET']));

            return $router->getRouteCollection()->get('domain-get-test');
        });

        $alternativeServers = $openApiDocument['paths']['/test']['get']['servers'] ?? [];
        $this->assertCount(1, $alternativeServers);
        $this->assertSame('http://{param}.localhost/api', $alternativeServers[0]['url']);
        $this->assertEmpty($openApiDocument['paths']['/test']['servers'] ?? []);
    }

    #[Test]
    public function doesntAddAnAlternativeServerWhenThereIsMatchingServer(): void
    {
        $container = $this->get('container');
        $container->setParameter('scramble.servers', [
            'Live' => 'http://{param}.localhost/api',
        ]);

        $openApiDocument = $this->generateForRoute(function () {
            $router = $this->get('router');
            $router->add('domain-get-test', new Route('/api/test', [], [], [], '{param}.localhost', [], ['GET']));

            return $router->getRouteCollection()->get('domain-get-test');
        });

        $container->setParameter('scramble.servers', null);

        $this->assertEmpty($openApiDocument['paths']['/test']['get']['servers'] ?? []);
        $this->assertEmpty($openApiDocument['paths']['/test']['servers'] ?? []);
    }

    #[Test]
    public function addsAnAlternativeServerWhenThereIsMatchingAndNotMatchingServers(): void
    {
        $container = $this->get('container');
        $container->setParameter('scramble.servers', [
            'Demo' => 'http://localhost/api',
            'Live' => 'http://{param}.localhost/api',
        ]);

        $openApiDocument = $this->generateForRoute(function () {
            $router = $this->get('router');
            $router->add('domain-get-test', new Route('/api/test', [], [], [], '{param}.localhost', [], ['GET']));

            return $router->getRouteCollection()->get('domain-get-test');
        });

        $container->setParameter('scramble.servers', null);

        $alternativeServers = $openApiDocument['paths']['/test']['servers'] ?? [];
        $this->assertCount(1, $alternativeServers);
        $this->assertSame([
            'url' => 'http://{param}.localhost/api',
            'description' => 'Live',
            'variables' => [
                'param' => [
                    'default' => 'example',
                ],
            ],
        ], $alternativeServers[0]);
    }

    #[Test]
    public function alternativeServerIsMovedToPathsWhenAllPathOperationsHaveIt(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            $router = $this->get('router');
            $router->add('post-test', new Route('/api/test', [], [], [], '{param}.localhost', [], ['POST']));
            $router->add('get-test', new Route('/api/test', [], [], [], '{param}.localhost', [], ['GET']));

            return $router->getRouteCollection()->get('get-test');
        });

        $alternativeServers = $openApiDocument['paths']['/test']['servers'] ?? [];
        $this->assertCount(1, $alternativeServers);
    }

    /**
     * Helper method to generate OpenAPI document for routes.
     * Adapted from Laravel's generateForRoute to work with Symfony.
     */
    protected function generateForRoute($param): array
    {
        return $param();
    }
}
