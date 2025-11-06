<?php

namespace Tests\Generator;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Arr;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Support\Facades\Route as RouteFacade;
use PHPUnit\Framework\Attributes\Test;
use Tests\SymfonyTestCase;
use Tests\AnalysisHelpers;

final class TagResolverTest extends SymfonyTestCase
{
    use AnalysisHelpers;

    #[Test]
    public function documentsTagsBasedOnResolveTagsUsing(): void
    {
        Scramble::resolveTagsUsing(function (RouteInfo $routeInfo) {
            return array_values(array_unique(
                Arr::map($routeInfo->phpDoc()->getTagsByName('@tags'), fn ($tag) => trim($tag?->value?->value))
            ));
        });

        $openApiDocument = $this->generateForRoute(function () {
            return RouteFacade::get('api/test', [ResolveTagDocumentationTestController::class, 'a']);
        });

        $this->assertArrayHasKey('tags', $openApiDocument['paths']['/test']['get']);
        $this->assertSame(['testTag'], $openApiDocument['paths']['/test']['get']['tags']);
    }
}

class ResolveTagDocumentationTestController extends \Illuminate\Routing\Controller
{
    /**
     * @tags testTag
     */
    public function a() {}
}
