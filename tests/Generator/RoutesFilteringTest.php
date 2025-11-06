<?php

namespace Dedoc\Scramble\Tests\Generator;

use Dedoc\Scramble\Attributes\ExcludeAllRoutesFromDocs;
use Dedoc\Scramble\Attributes\ExcludeRouteFromDocs;
use Dedoc\Scramble\Scramble;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use PHPUnit\Framework\Attributes\Test;
use SymfonyTestCase;

final class RoutesFilteringTest extends SymfonyTestCase
{
    #[Test]
    public function filtersRoutesWithExcludeRouteFromDocsAttribute(): void
    {
        $documentation = $this->generateForRoutes(fn () => [
            RouteFacade::post('foo', [RoutesFilteringTest_ControllerA::class, 'foo']),
            RouteFacade::post('bar', [RoutesFilteringTest_ControllerA::class, 'bar']),
        ]);

        $this->assertSame(['/foo'], array_keys($documentation['paths']));
    }

    #[Test]
    public function filtersAllControllerRoutesWithExcludeAllRoutesFromDocsAttribute(): void
    {
        $documentation = $this->generateForRoutes(fn () => [
            RouteFacade::post('foo', [RoutesFilteringTest_ControllerB::class, 'foo']),
            RouteFacade::post('bar', [RoutesFilteringTest_ControllerB::class, 'bar']),
        ]);

        $this->assertSame([], array_keys($documentation['paths'] ?? []));
    }

    protected function generateForRoutes($callback)
    {
        $routesUris = array_map(
            fn (Route $r) => $r->uri,
            $callback()
        );

        Scramble::routes(fn (Route $r) => in_array($r->uri, $routesUris));

        return app()->make(\Dedoc\Scramble\Generator::class)();
    }
}

class RoutesFilteringTest_ControllerA
{
    public function foo() {}

    #[ExcludeRouteFromDocs]
    public function bar() {}
}

#[ExcludeAllRoutesFromDocs]
class RoutesFilteringTest_ControllerB
{
    public function foo() {}

    public function bar() {}
}
