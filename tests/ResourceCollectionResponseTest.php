<?php

namespace Dedoc\Scramble\Tests;

use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Infer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\Components;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\CollectionToSchema;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\JsonResourceTypeToSchema;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\ResourceCollectionTypeToSchema;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use Illuminate\Http\Request;
use Spatie\Snapshots\MatchesSnapshots;

final class ResourceCollectionResponseTest extends SymfonyTestCase
{
    use MatchesSnapshots;

    private Components $components;
    private OpenApiContext $context;
    private TypeTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->components = new Components;
        $this->context = new OpenApiContext((new OpenApi('3.1.0'))->setComponents($this->components), new GeneratorConfig);
        $this->transformer = $this->get(TypeTransformer::class, [
            'context' => $this->context,
        ]);
    }

    public function testTransformsCollectionWithToArrayOnly(): void
    {
        $transformer = new TypeTransformer($infer = $this->get(Infer::class), $this->context, [
            CollectionToSchema::class,
            JsonResourceTypeToSchema::class,
            ResourceCollectionTypeToSchema::class,
        ]);
        $extension = new ResourceCollectionTypeToSchema($infer, $transformer, $this->components, $this->context);

        $type = new ObjectType(UserCollection_One::class);

        $this->assertMatchesSnapshot($extension->toSchema($type)->toArray());
    }

    public function testTransformsCollectionWithToArrayAndWith(): void
    {
        $transformer = new TypeTransformer($infer = $this->get(Infer::class), $this->context, [
            CollectionToSchema::class,
            JsonResourceTypeToSchema::class,
            ResourceCollectionTypeToSchema::class,
        ]);
        $extension = new ResourceCollectionTypeToSchema($infer, $transformer, $this->components, $this->context);

        $type = new ObjectType(UserCollection_Two::class);

        $this->assertMatchesSnapshot($extension->toSchema($type)->toArray());
    }

    public function testTransformsCollectionWithoutProperToArrayImplementation(): void
    {
        $type = new ObjectType(UserCollection_Three::class);

        $this->assertMatchesSnapshot([
            'response' => $this->transformer->toResponse($type)->toArray(),
            'components' => $this->components->toArray(),
        ]);
    }

    public function testTransformsCollectionWithoutToArrayImplementation(): void
    {
        $type = new ObjectType(UserCollection_Four::class);

        $this->assertMatchesSnapshot([
            'response' => $this->transformer->toResponse($type)->toArray(),
            'components' => $this->components->toArray(),
        ]);
    }

    public function testAttachesAdditionalDataToTheResponseDocumentation(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [ResourceCollectionResponseTest_Controller::class, 'index']);
        });

        $this->assertMatchesSnapshot($openApiDocument);
    }

    public function testAttachesAdditionalDataToTheResponseDocumentationForAnnotation(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [AnnotationResourceCollectionResponseTest_Controller::class, 'index']);
        });

        $props = $openApiDocument['paths']['/test']['get']['responses'][200]['content']['application/json']['schema']['properties'];
        $this->assertArrayHasKey('data', $props);
        $this->assertArrayHasKey('something', $props);
        $this->assertSame(['foo' => ['type' => 'string', 'enum' => ['bar']]], $props['something']['properties']);
    }

    public function testTransformsCollectionWithPaginationInformationImplementation(): void
    {
        $type = getStatementType('new '.UserCollection_Five::class.'('.\Dedoc\Scramble\Tests\Fixtures\Laravel\Models\SampleUserModel::class.'::paginate())');

        $this->assertMatchesSnapshot($this->transformer->toResponse($type)->toArray());
    }

    public function testTransformsCollectionWithFullyCustomPaginationInformation(): void
    {
        $type = getStatementType('new '.UserCollection_Six::class.'('.\Dedoc\Scramble\Tests\Fixtures\Laravel\Models\SampleUserModel::class.'::paginate())');

        $this->assertMatchesSnapshot($this->transformer->toResponse($type)->toArray());
    }

    public function testTransformsCollectionWithPaginationInformationAndFetchingFromPaginatedArray(): void
    {
        $type = getStatementType('new '.UserCollection_Seven::class.'('.\Dedoc\Scramble\Tests\Fixtures\Laravel\Models\SampleUserModel::class.'::paginate())');

        $this->assertMatchesSnapshot($this->transformer->toResponse($type)->toArray());
    }

    public function testTransformsCollectionWithPaginationInformationAndUnset(): void
    {
        $type = getStatementType('new '.UserCollection_Eight::class.'('.\Dedoc\Scramble\Tests\Fixtures\Laravel\Models\SampleUserModel::class.'::paginate())');

        $this->assertMatchesSnapshot($this->transformer->toResponse($type)->toArray());
    }
}

class UserCollection_One extends \Illuminate\Http\Resources\Json\ResourceCollection
{
    public $collects = UserResource::class;

    public function toArray($request)
    {
        return [
            $this->merge(['foo' => 'bar']),
            'users' => $this->collection,
            'meta' => [
                'foo' => 'bar',
            ],
        ];
    }
}

class UserCollection_Two extends \Illuminate\Http\Resources\Json\ResourceCollection
{
    public $collects = UserResource::class;

    public function toArray($request)
    {
        return [
            $this->merge(['foo' => 'bar']),
            'users' => $this->collection,
            'meta' => [
                'foo' => 'bar',
            ],
        ];
    }

    public function with($request)
    {
        return [
            'some' => 'data',
        ];
    }
}

class UserCollection_Three extends \Illuminate\Http\Resources\Json\ResourceCollection
{
    public $collects = UserResource::class;

    public function toArray($request)
    {
        return parent::toArray($request);
    }
}

class UserCollection_Four extends \Illuminate\Http\Resources\Json\ResourceCollection
{
    public $collects = UserResource::class;
}

class UserCollection_Five extends \Illuminate\Http\Resources\Json\ResourceCollection
{
    public $collects = UserResource::class;

    public function paginationInformation($request, $paginated, $default): array
    {
        $default['links']['custom'] = 'https://example.com';

        return $default;
    }
}

class UserCollection_Six extends \Illuminate\Http\Resources\Json\ResourceCollection
{
    public $collects = UserResource::class;

    public function paginationInformation($request, $paginated, $default): array
    {
        // Have to ignore phpstan errors here because the base class has a very restrictive array shape.
        return [ // @phpstan-ignore-line
            'links' => [
                'first' => $default['links']['first'],
                'last' => $default['links']['last'],
                'prev' => $default['links']['prev'],
                'next' => $default['links']['next'],
            ],
            'meta' => [
                'currentPage' => $default['meta']['current_page'], // @phpstan-ignore-line
                'lastPage' => $default['meta']['last_page'], // @phpstan-ignore-line
                'from' => $default['meta']['from'], // @phpstan-ignore-line
                'to' => $default['meta']['to'], // @phpstan-ignore-line
                'total' => $default['meta']['total'], // @phpstan-ignore-line
                'pageSize' => $default['meta']['per_page'], // @phpstan-ignore-line
                'path' => $default['meta']['path'], // @phpstan-ignore-line
            ],
        ];
    }
}

class UserCollection_Seven extends \Illuminate\Http\Resources\Json\ResourceCollection
{
    public $collects = UserResource::class;

    public function paginationInformation($request, $paginated, $default): array
    {
        return [
            'page' => $paginated['current_page'],
            'totalPages' => $paginated['last_page'],
        ];
    }
}

class UserCollection_Eight extends \Illuminate\Http\Resources\Json\ResourceCollection
{
    public $collects = UserResource::class;

    public function paginationInformation($request, $paginated, $default): array
    {
        unset($default['links']['prev'], $default['links']['next']);
        unset($default['meta']);

        return $default;
    }
}

class ResourceCollectionResponseTest_Controller
{
    public function index(Request $request)
    {
        return (new UserCollection_One)
            ->additional([
                'something' => ['foo' => 'bar'],
            ]);
    }
}

class AnnotationResourceCollectionResponseTest_Controller
{
    public function index(Request $request)
    {
        return UserResource::collection(collect())
            ->additional([
                'something' => ['foo' => 'bar'],
            ]);
    }
}

class UserResource extends \Illuminate\Http\Resources\Json\JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => 1,
        ];
    }
}
