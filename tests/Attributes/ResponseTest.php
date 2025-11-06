<?php

namespace Dedoc\Scramble\Tests\Attributes;

use Dedoc\Scramble\Attributes\Response;
use Dedoc\Scramble\Tests\Support\AnalysisHelpers;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    use AnalysisHelpers;

    #[Test]
    public function generatesResponseForBasicCase(): void
    {
        $openApiDocument = $this->generateForRoute(fn () => Route::get('test', AController_ResponseTest::class));

        $responses = $openApiDocument['paths']['/test']['get']['responses'];

        $this->assertCount(1, $responses);
        $this->assertSame('Nice response', $responses[200]['description']);
        $this->assertSame([
            'type' => 'object',
            'properties' => ['foo' => ['type' => 'string']],
            'required' => ['foo'],
        ], $responses[200]['content']['application/json']['schema']);
    }

    #[Test]
    public function allowsAddingAdditionalMediaTypesResponseWithoutOverridingTheOriginalType(): void
    {
        $openApiDocument = $this->generateForRoute(fn () => Route::get('test', MultipleTypeController_ResponseTest::class));

        $responses = $openApiDocument['paths']['/test']['get']['responses'];

        $this->assertCount(1, $responses);
        $this->assertSame('When `download` set to `false`, returns the JSON response; when `true`, returns the excel', $responses[200]['description']);
        $this->assertSame([
            'type' => 'object',
            'properties' => ['foo' => ['type' => 'string']],
            'required' => ['foo'],
        ], $responses[200]['content']['application/json']['schema']);
        $this->assertSame([
            'type' => 'string',
            'format' => 'binary',
        ], $responses[200]['content']['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']['schema']);
    }

    #[Test]
    public function allowsAddingNewResponse(): void
    {
        $openApiDocument = $this->generateForRoute(fn () => Route::get('test', NewResponseController_ResponseTest::class));

        $responses = $openApiDocument['paths']['/test']['get']['responses'];

        $this->assertCount(2, $responses);
        $this->assertArrayHasKey(200, $responses);
        $this->assertArrayHasKey(201, $responses);
        $this->assertSame([
            'type' => 'object',
            'properties' => ['foo' => ['type' => 'string']],
            'required' => ['foo'],
        ], $responses[201]['content']['application/json']['schema']);
    }
}

class AController_ResponseTest
{
    #[Response(200, 'Nice response', type: 'array{"foo": string}')]
    public function __invoke()
    {
        return something_unknown();
    }
}

class MultipleTypeController_ResponseTest
{
    #[Response(200, 'When `download` set to `false`, returns the JSON response; when `true`, returns the excel')]
    #[Response(200, mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', type: 'string', format: 'binary')]
    public function __invoke()
    {
        /**
         * @body array{"foo":string}
         */
        return something_unknown();
    }
}

class NewResponseController_ResponseTest
{
    #[Response(201, type: 'array{foo: string}')]
    public function __invoke()
    {
        return something_unknown();
    }
}
