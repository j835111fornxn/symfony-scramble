<?php

namespace Dedoc\Scramble\Tests\Support\TypeToSchemaExtensions;

use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

final class AnonymousResourceCollectionTypeToSchemaTest extends SymfonyTestCase
{
    private TypeTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $context = new OpenApiContext(new OpenApi('3.1.0'), new GeneratorConfig);

        $this->transformer = $this->get(TypeTransformer::class, [
            'context' => $context,
        ]);
    }

    public function testDocumentsInferredPaginationResponse(): void
    {
        $openApiDocument = $this->generateForRoute(fn () => $this->addRoute('/test', InferredPagination_AnonymousResourceCollectionTypeToSchemaTestController::class));

        $responses = $openApiDocument['paths']['/test']['get']['responses'];
        $this->assertArrayHasKey(200, $responses);

        $schema = $responses[200]['content']['application/json']['schema'];
        $this->assertArrayHasKey('type', $schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('data', $schema['properties']);
        $this->assertArrayHasKey('meta', $schema['properties']);
        $this->assertArrayHasKey('links', $schema['properties']);
    }
    public function testDocumentsManuallyCreatedResponse(): void
    {
        $type = getStatementType(UserResource_AnonymousResourceCollectionTypeToSchemaTest::class.'::collection()->response()->setStatusCode(202)');

        $response = $this->transformer->toResponse($type);

        $this->assertSame(202, $response->code);
        $this->assertSame('Array of `UserResource_AnonymousResourceCollectionTypeToSchemaTest`', $response->toArray()['description']);
    }

    public function testDocumentsManuallyAnnotatedResponse(): void
    {
        $openApiDocument = $this->generateForRoute(fn () => $this->addRoute('/test', ManualResponse_AnonymousResourceCollectionTypeToSchemaTestController::class));

        $response = $openApiDocument['paths']['/test']['get']['responses'][200];

        $this->assertSame([
            'type' => 'array',
            'items' => [
                '$ref' => '#/components/schemas/UserResource_AnonymousResourceCollectionTypeToSchemaTest',
            ],
        ], $response['content']['application/json']['schema']['properties']['data']);
        $this->assertSame('Paginated set of `UserResource_AnonymousResourceCollectionTypeToSchemaTest`', $response['description']);
    }
}

class User_AnonymousResourceCollectionTypeToSchemaTest extends Model
{
    protected $table = 'users';
}

/**
 * @mixin User_AnonymousResourceCollectionTypeToSchemaTest
 */
class UserResource_AnonymousResourceCollectionTypeToSchemaTest extends JsonResource
{
    public function toArray(Request $request)
    {
        return ['id' => $this->id];
    }
}

class InferredPagination_AnonymousResourceCollectionTypeToSchemaTestController
{
    public function __invoke()
    {
        return UserResource_AnonymousResourceCollectionTypeToSchemaTest::collection(User_AnonymousResourceCollectionTypeToSchemaTest::paginate());
    }
}

class ManualResponse_AnonymousResourceCollectionTypeToSchemaTestController
{
    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<UserResource_AnonymousResourceCollectionTypeToSchemaTest>>
     */
    public function __invoke()
    {
        return UserResource_AnonymousResourceCollectionTypeToSchemaTest::collection(User_AnonymousResourceCollectionTypeToSchemaTest::all())->response();
    }
}
