<?php

namespace Dedoc\Scramble\Tests\Support\OperationExtensions;

use Dedoc\Scramble\Attributes\Example;
use Dedoc\Scramble\Attributes\Header;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use Illuminate\Http\JsonResponse;

final class ResponseHeadersTest extends SymfonyTestCase
{
    public function testAddsHeadersTo200ResponseDocumentation(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [ResponseHeadersTestController::class, 'ok']);
        });

        $responses = $openApiDocument['paths']['/test']['get']['responses'];

        $this->assertArrayHasKey('200', $responses);
        $this->assertArrayHasKey('headers', $responses['200']);
        $this->assertArrayHasKey('X-Rate-Limit', $responses['200']['headers']);
        $this->assertArrayHasKey('description', $responses['200']['headers']['X-Rate-Limit']);
        $this->assertSame('Rate limiting information', $responses['200']['headers']['X-Rate-Limit']['description']);
    }

    public function testAddsHeadersWithExamplesTo200Response(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [ResponseHeadersTestController::class, 'ok']);
        });

        $responses = $openApiDocument['paths']['/test']['get']['responses'];

        $this->assertArrayHasKey('example', $responses['200']['headers']['X-Correlation-ID']);
        $this->assertSame('123e4567-e89b-12d3-a456-426614174000', $responses['200']['headers']['X-Correlation-ID']['example']);
    }

    public function testAddsHeadersFor201StatusCode(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [ResponseHeadersTestController::class, 'created']);
        });

        $responses = $openApiDocument['paths']['/test']['get']['responses'];

        $this->assertArrayHasKey('201', $responses);
        $this->assertArrayHasKey('headers', $responses['201']);
        $this->assertArrayHasKey('X-Created-At', $responses['201']['headers']);
        $this->assertSame('Creation timestamp', $responses['201']['headers']['X-Created-At']['description']);
    }

    public function testAddsMultipleHeadersForTheSameStatusCode200(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [ResponseHeadersTestController::class, 'ok']);
        });

        $responses = $openApiDocument['paths']['/test']['get']['responses'];

        $this->assertArrayHasKey('X-Rate-Limit', $responses['200']['headers']);
        $this->assertArrayHasKey('X-Correlation-ID', $responses['200']['headers']);
        $this->assertArrayHasKey('X-API-Version', $responses['200']['headers']);
    }

    public function testAddsHeadersWithMultipleExamplesTo200Response(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [ResponseHeadersTestController::class, 'ok']);
        });

        $responses = $openApiDocument['paths']['/test']['get']['responses'];

        $this->assertArrayHasKey('examples', $responses['200']['headers']['X-API-Version']);
        $this->assertArrayHasKey('v1', $responses['200']['headers']['X-API-Version']['examples']);
        $this->assertArrayHasKey('v2', $responses['200']['headers']['X-API-Version']['examples']);
    }

    public function testAppliesWildcardHeadersToAllResponses(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [ResponseHeadersTestController::class, 'withWildcardHeaders']);
        });

        $responses = $openApiDocument['paths']['/test']['get']['responses'];

        $this->assertArrayHasKey('X-Request-ID', $responses['200']['headers']);
        $this->assertSame('Request ID for tracing', $responses['200']['headers']['X-Request-ID']['description']);
    }

    public function testMixesDifferentHeaderTypesCorrectly(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [ResponseHeadersTestController::class, 'mixedHeaders']);
        });

        $responses = $openApiDocument['paths']['/test']['get']['responses'];

        $this->assertArrayHasKey('X-Request-ID', $responses['200']['headers']);
        $this->assertArrayHasKey('X-Rate-Limit', $responses['200']['headers']);
        $this->assertArrayNotHasKey('X-Error-Code', $responses['200']['headers']);
    }

    public function testAppliesWildcardHeadersToAllResponsesWhenMultipleResponsesExist(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [ResponseHeadersTestController::class, 'multipleResponses']);
        });

        $responses = $openApiDocument['paths']['/test']['get']['responses'];

        $this->assertArrayHasKey('200', $responses);
        $this->assertArrayHasKey('404', $responses);

        $this->assertArrayHasKey('X-Request-ID', $responses['200']['headers']);
        $this->assertArrayHasKey('X-Request-ID', $responses['404']['headers']);

        $this->assertArrayHasKey('X-Rate-Limit', $responses['200']['headers']);
        $this->assertArrayNotHasKey('X-Rate-Limit', $responses['404']['headers']);

        $this->assertArrayHasKey('X-Error-Code', $responses['404']['headers']);
        $this->assertArrayNotHasKey('X-Error-Code', $responses['200']['headers']);
    }

    public function testRemovesUnusedResponseReferencesWhenDereferenced(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [ResponseHeadersTestController::class, 'withUnusedReferences']);
        });

        $responses = $openApiDocument['paths']['/test']['get']['responses'];

        $this->assertArrayHasKey('X-Custom-Header', $responses['404']['headers']);
        $this->assertArrayNotHasKey('components', $openApiDocument);
    }

    public function testAddsHeaderWithTypeSpecification(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [ResponseHeadersTestController::class, 'withType']);
        });

        $responses = $openApiDocument['paths']['/test']['get']['responses'];

        $this->assertArrayHasKey('schema', $responses['200']['headers']['X-Count']);
        $this->assertArrayHasKey('type', $responses['200']['headers']['X-Count']['schema']);
        $this->assertSame('integer', $responses['200']['headers']['X-Count']['schema']['type']);
    }

    public function testAddsHeaderWithFormatSpecification(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [ResponseHeadersTestController::class, 'withFormat']);
        });

        $responses = $openApiDocument['paths']['/test']['get']['responses'];

        $this->assertArrayHasKey('schema', $responses['200']['headers']['X-Date']);
        $this->assertArrayHasKey('format', $responses['200']['headers']['X-Date']['schema']);
        $this->assertSame('date', $responses['200']['headers']['X-Date']['schema']['format']);
    }

    public function testAddsHeaderWithDefaultValue(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [ResponseHeadersTestController::class, 'withDefault']);
        });

        $responses = $openApiDocument['paths']['/test']['get']['responses'];

        $this->assertArrayHasKey('schema', $responses['200']['headers']['X-Language']);
        $this->assertArrayHasKey('default', $responses['200']['headers']['X-Language']['schema']);
        $this->assertSame('en', $responses['200']['headers']['X-Language']['schema']['default']);
    }

    public function testAddsHeaderWithTypeFormatAndDefaultCombined(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [ResponseHeadersTestController::class, 'withTypeFormatAndDefault']);
        });

        $responses = $openApiDocument['paths']['/test']['get']['responses'];

        $this->assertArrayHasKey('schema', $responses['200']['headers']['X-Timestamp']);
        $this->assertArrayHasKey('type', $responses['200']['headers']['X-Timestamp']['schema']);
        $this->assertSame('string', $responses['200']['headers']['X-Timestamp']['schema']['type']);
        $this->assertArrayHasKey('format', $responses['200']['headers']['X-Timestamp']['schema']);
        $this->assertSame('date-time', $responses['200']['headers']['X-Timestamp']['schema']['format']);
        $this->assertArrayHasKey('default', $responses['200']['headers']['X-Timestamp']['schema']);
        $this->assertSame('2024-01-01T00:00:00Z', $responses['200']['headers']['X-Timestamp']['schema']['default']);
    }

    public function testAddsHeaderWithBooleanType(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [ResponseHeadersTestController::class, 'withBooleanType']);
        });

        $responses = $openApiDocument['paths']['/test']['get']['responses'];

        $this->assertArrayHasKey('schema', $responses['200']['headers']['X-Cache-Enabled']);
        $this->assertArrayHasKey('type', $responses['200']['headers']['X-Cache-Enabled']['schema']);
        $this->assertSame('boolean', $responses['200']['headers']['X-Cache-Enabled']['schema']['type']);
    }

    public function testAddsHeaderWithArrayType(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [ResponseHeadersTestController::class, 'withArrayType']);
        });

        $responses = $openApiDocument['paths']['/test']['get']['responses'];

        $this->assertArrayHasKey('schema', $responses['200']['headers']['X-Allowed-Origins']);
        $this->assertArrayHasKey('type', $responses['200']['headers']['X-Allowed-Origins']['schema']);
        $this->assertSame('array', $responses['200']['headers']['X-Allowed-Origins']['schema']['type']);
    }

    public function testAddsHeaderWithRequiredSpecification(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [ResponseHeadersTestController::class, 'withRequired']);
        });

        $responses = $openApiDocument['paths']['/test']['get']['responses'];

        $this->assertArrayHasKey('required', $responses['200']['headers']['X-Authorization']);
        $this->assertTrue($responses['200']['headers']['X-Authorization']['required']);
    }

    public function testAddsHeaderWithDeprecatedSpecification(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [ResponseHeadersTestController::class, 'withDeprecated']);
        });

        $responses = $openApiDocument['paths']['/test']['get']['responses'];

        $this->assertArrayHasKey('deprecated', $responses['200']['headers']['X-Legacy-Header']);
        $this->assertTrue($responses['200']['headers']['X-Legacy-Header']['deprecated']);
    }

    public function testAddsHeaderWithExplodeSpecification(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [ResponseHeadersTestController::class, 'withExplode']);
        });

        $responses = $openApiDocument['paths']['/test']['get']['responses'];

        $this->assertArrayHasKey('explode', $responses['200']['headers']['X-Tags']);
        $this->assertTrue($responses['200']['headers']['X-Tags']['explode']);
    }
}

class ResponseHeadersTestController
{
    #[Header('X-Rate-Limit', 'Rate limiting information')]
    #[Header('X-Correlation-ID', 'Correlation ID for tracing', example: '123e4567-e89b-12d3-a456-426614174000')]
    #[Header(
        'X-API-Version',
        'API version header',
        examples: [
            'v1' => new Example('v1', 'Version 1'),
            'v2' => new Example('v2', 'Version 2'),
        ]
    )]
    public function ok()
    {
        return new JsonResponse(['data' => 'test'], 200);
    }

    #[Header('X-Created-At', 'Creation timestamp', status: 201)]
    public function created()
    {
        return new JsonResponse(['data' => 'created'], 201);
    }

    #[Header('X-Request-ID', 'Request ID for tracing', status: '*')]
    public function withWildcardHeaders()
    {
        return new JsonResponse(['data' => 'success'], 200);
    }

    #[Header('X-Request-ID', 'Request ID for tracing', status: '*')]
    #[Header('X-Rate-Limit', 'Rate limiting information')]
    #[Header('X-Error-Code', 'Error code', status: 404)]
    public function mixedHeaders()
    {
        return new JsonResponse(['data' => 'success'], 200);
    }

    #[Header('X-Request-ID', 'Request ID for tracing', status: '*')]
    #[Header('X-Rate-Limit', 'Rate limiting information')]
    #[Header('X-Error-Code', 'Error code', status: 404)]
    public function multipleResponses()
    {
        if (request()->has('error')) {
            abort(404, 'Not found');
        }

        return new JsonResponse(['data' => 'success'], 200);
    }

    #[Header('X-Custom-Header', 'Custom header description', status: 404)]
    public function withUnusedReferences()
    {
        if (request()->has('not_found')) {
            abort(404, 'Resource not found');
        }

        return new JsonResponse(['data' => 'success'], 200);
    }

    #[Header('X-Count', 'Count header', type: 'int')]
    public function withType()
    {
        return new JsonResponse(['data' => 'test'], 200);
    }

    #[Header('X-Date', format: 'date')]
    public function withFormat()
    {
        return new JsonResponse(['data' => 'test'], 200);
    }

    #[Header('X-Language', 'Language header', default: 'en')]
    public function withDefault()
    {
        return new JsonResponse(['data' => 'test'], 200);
    }

    #[Header('X-Timestamp', 'Timestamp header', type: 'string', format: 'date-time', default: '2024-01-01T00:00:00Z')]
    public function withTypeFormatAndDefault()
    {
        return new JsonResponse(['data' => '2024-01-01T00:00:00Z'], 200);
    }

    #[Header('X-Cache-Enabled', 'Cache enabled header', type: 'bool')]
    public function withBooleanType()
    {
        return new JsonResponse(['data' => true], 200);
    }

    #[Header('X-Allowed-Origins', 'Allowed origins header', type: 'array')]
    public function withArrayType()
    {
        return new JsonResponse(['data' => ['http://example.com']], 200);
    }

    #[Header('X-Authorization', 'Authorization header', required: true)]
    public function withRequired()
    {
        return new JsonResponse(['data' => 'test'], 200);
    }

    #[Header('X-Legacy-Header', 'Legacy header', deprecated: true)]
    public function withDeprecated()
    {
        return new JsonResponse(['data' => 'test'], 200);
    }

    #[Header('X-Tags', 'Tags header', explode: true)]
    public function withExplode()
    {
        return new JsonResponse(['data' => ['tag1', 'tag2']], 200);
    }
}
