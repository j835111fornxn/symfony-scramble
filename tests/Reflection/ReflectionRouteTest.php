<?php

namespace Dedoc\Scramble\Tests\Reflection;

use Dedoc\Scramble\Reflection\ReflectionRoute;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route as RouteFacade;
use PHPUnit\Framework\Attributes\Test;

final class ReflectionRouteTest extends SymfonyTestCase
{
    #[Test]
    public function createSingleInstanceFromRoute(): void
    {
        $route = RouteFacade::get('_', fn () => null);

        $ra = ReflectionRoute::createFromRoute($route);
        $rb = ReflectionRoute::createFromRoute($route);

        $this->assertTrue($ra === $rb);
    }

    #[Test]
    public function getsParamsAliasesSingleInstanceFromRoute(): void
    {
        $route = RouteFacade::get('{test_id}', fn (string $testId) => null);

        $this->assertSame([
            'test_id' => 'testId',
        ], ReflectionRoute::createFromRoute($route)->getSignatureParametersMap());
    }

    #[Test]
    public function getsParamsAliasesWithoutRequestFromRoute(): void
    {
        $route = RouteFacade::get('{test_id}', fn (Request $request, string $testId) => null);

        $this->assertSame([
            'test_id' => 'testId',
        ], ReflectionRoute::createFromRoute($route)->getSignatureParametersMap());
    }

    #[Test]
    public function getsBoundParamsTypes(): void
    {
        $r = ReflectionRoute::createFromRoute(
            RouteFacade::get('{test_id}', fn (Request $request, User_ReflectionRouteTest $testId) => null)
        );

        $this->assertSame([
            'test_id' => User_ReflectionRouteTest::class,
        ], $r->getBoundParametersTypes());
    }
}

class User_ReflectionRouteTest extends Model
{
}
