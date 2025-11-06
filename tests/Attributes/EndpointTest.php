<?php

namespace Dedoc\Scramble\Tests\Attributes;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Tests\Support\AnalysisHelpers;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EndpointTest extends TestCase
{
    use AnalysisHelpers;

    #[Test]
    public function attachesOperationIdToControllerAction(): void
    {
        $openApiDocument = $this->generateForRoute(fn () => Route::get('test', AController_EndpointTest::class));

        $this->assertSame('do_something_magic', $openApiDocument['paths']['/test']['get']['operationId']);
    }
}

class AController_EndpointTest
{
    #[Endpoint(operationId: 'do_something_magic')]
    public function __invoke()
    {
        return something_unknown();
    }
}
