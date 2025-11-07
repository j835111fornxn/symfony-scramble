<?php

namespace Dedoc\Scramble\Tests\Generator;

use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\Test;

final class AbortHelpersResponseDocumentationTest extends SymfonyTestCase
{
    #[Test]
    public function documentsAbortHelperWith404StatusAsReferencedErrorResponse(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            // Using Symfony routing instead of Laravel's RouteFacade
            $router = $this->get('router');
            $router->add('test_abort_404', new \Symfony\Component\Routing\Route('/test', [], [], [], null, null, ['POST']));
            return $router->getRouteCollection()->get('test_abort_404');
        });

        $response = $openApiDocument['paths']['/test']['post']['responses'][404];
        $this->assertArrayHasKey('$ref', $response);
        $this->assertSame('#/components/responses/ModelNotFoundException', $response['$ref']);
    }

    #[Test]
    public function documentsAbortHelperAsNotReferencedErrorResponse(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            $router = $this->get('router');
            $router->add('test_abort', new \Symfony\Component\Routing\Route('/test', [], [], [], null, null, ['POST']));
            return $router->getRouteCollection()->get('test_abort');
        });

        $response = $openApiDocument['paths']['/test']['post']['responses'][400];
        $this->assertArrayHasKey('description', $response);
        $this->assertArrayHasKey('content', $response);
        $this->assertArrayNotHasKey('$ref', $response);
        $this->assertSame('Something is wrong.', $response['content']['application/json']['schema']['properties']['message']['example']);
    }

    #[Test]
    public function documentsAbortIfHelper(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            $router = $this->get('router');
            $router->add('test_abort_if', new \Symfony\Component\Routing\Route('/test', [], [], [], null, null, ['POST']));
            return $router->getRouteCollection()->get('test_abort_if');
        });

        $response = $openApiDocument['paths']['/test']['post']['responses'][402];
        $this->assertArrayHasKey('description', $response);
        $this->assertArrayHasKey('content', $response);
        $this->assertArrayNotHasKey('$ref', $response);
        $this->assertSame('Something is wrong.', $response['content']['application/json']['schema']['properties']['message']['example']);
    }

    #[Test]
    public function documentsAbortUnlessHelper(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            $router = $this->get('router');
            $router->add('test_abort_unless', new \Symfony\Component\Routing\Route('/test', [], [], [], null, null, ['POST']));
            return $router->getRouteCollection()->get('test_abort_unless');
        });

        $response = $openApiDocument['paths']['/test']['post']['responses'][403];
        $this->assertArrayHasKey('description', $response);
        $this->assertArrayHasKey('content', $response);
        $this->assertArrayNotHasKey('$ref', $response);
        $this->assertSame('Something is wrong.', $response['content']['application/json']['schema']['properties']['message']['example']);
    }
}
