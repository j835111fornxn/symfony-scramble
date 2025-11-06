<?php

namespace Dedoc\Scramble\Tests\Generator\Request;

use Dedoc\Scramble\Tests\SymfonyTestCase;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Concerns\HasVersion4Uuids;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route as RouteFacade;
use PHPUnit\Framework\Attributes\Test;

final class ParametersDocumentationTest extends SymfonyTestCase
{
    #[Test]
    public function documentsModelKeysUuidParametersAsUuids(): void
    {
        if (! trait_exists(HasUuids::class)) {
            $this->markTestSkipped('HasUuids trait not available');
        }

        $openApiDocument = $this->generateForRoute(fn () => RouteFacade::get('api/test/{model}', [
            DocumentsModelKeysUuidParametersAsUuids_Test::class, 'index',
        ]));

        $params = $openApiDocument['paths']['/test/{model}']['get']['parameters'];
        $this->assertCount(1, $params);
        $this->assertSame([
            'name' => 'model',
            'in' => 'path',
            'required' => true,
            'schema' => [
                'type' => 'string',
                'format' => 'uuid',
            ],
        ], $params[0]);
    }

    #[Test]
    public function documentsModelKeysUuidV4ParametersAsUuids(): void
    {
        if (! trait_exists(HasVersion4Uuids::class)) {
            $this->markTestSkipped('HasVersion4Uuids trait not available');
        }

        $openApiDocument = $this->generateForRoute(fn () => RouteFacade::get('api/test/{model}', [
            DocumentsModelKeysUuidV4ParametersAsUuids_Test::class, 'index',
        ]));

        $params = $openApiDocument['paths']['/test/{model}']['get']['parameters'];
        $this->assertCount(1, $params);
        $this->assertSame([
            'name' => 'model',
            'in' => 'path',
            'required' => true,
            'schema' => [
                'type' => 'string',
                'format' => 'uuid',
            ],
        ], $params[0]);
    }

    #[Test]
    public function supportsFormatAnnotationForValidationRules(): void
    {
        $openApiDocument = $this->generateForRoute(fn () => RouteFacade::get('api/test', SupportFormatAnnotation_ParametersDocumentationTestController::class));

        $parameters = $openApiDocument['paths']['/test']['get']['parameters'];
        $this->assertCount(1, $parameters);
        $this->assertSame([
            'name' => 'foo',
            'in' => 'query',
            'required' => true,
            'schema' => [
                'type' => 'string',
                'format' => 'uuid',
            ],
        ], $parameters[0]);
    }

    #[Test]
    public function supportsOptionalParameters(): void
    {
        $openApiDocument = $this->generateForRoute(fn () => RouteFacade::get('api/test/{payment_preference?}', SupportOptionalParam_ParametersDocumentationTestController::class));

        $parameters = $openApiDocument['paths']['/test/{paymentPreference}']['get']['parameters'];
        $this->assertCount(1, $parameters);
        $this->assertSame([
            'name' => 'paymentPreference',
            'in' => 'path',
            'required' => true,
            'description' => '**Optional**. The name of the payment preference to use',
            'schema' => [
                'type' => ['string', 'null'],
                'default' => 'paypal',
            ],
            'x-optional' => true,
        ], $parameters[0]);
    }
}

class DocumentsModelKeysUuidParametersAsUuids_Test
{
    public function index(DocumentsModelKeysUuidParametersAsUuids_Model $model)
    {
        return response()->json();
    }
}

class DocumentsModelKeysUuidParametersAsUuids_Model extends \Illuminate\Database\Eloquent\Model
{
    use HasUuids;
}

class DocumentsModelKeysUuidV4ParametersAsUuids_Test
{
    public function index(DocumentsModelKeysUuidV4ParametersAsUuids_Model $model)
    {
        return response()->json();
    }
}

class DocumentsModelKeysUuidV4ParametersAsUuids_Model extends \Illuminate\Database\Eloquent\Model
{
    use HasVersion4Uuids;
}

class SupportFormatAnnotation_ParametersDocumentationTestController
{
    public function __invoke(Request $request)
    {
        $request->validate([
            /** @format uuid */
            'foo' => ['required'],
        ]);
    }
}

class SupportOptionalParam_ParametersDocumentationTestController
{
    /**
     * @param  string|null  $paymentPreference  The name of the payment preference to use
     */
    public function __invoke(?string $paymentPreference = 'paypal') {}
}
