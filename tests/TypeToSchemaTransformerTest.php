<?php

namespace Dedoc\Scramble\Tests;

use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Infer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\Type\ArrayItemType_;
use Dedoc\Scramble\Support\Type\ArrayType;
use Dedoc\Scramble\Support\Type\BooleanType;
use Dedoc\Scramble\Support\Type\IntegerType;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Type\Literal\LiteralFloatType;
use Dedoc\Scramble\Support\Type\Literal\LiteralStringType;
use Dedoc\Scramble\Support\Type\MixedType;
use Dedoc\Scramble\Support\Type\NullType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\StringType;
use Dedoc\Scramble\Support\Type\Union;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\AnonymousResourceCollectionTypeToSchema;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\EnumToSchema;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\JsonResourceTypeToSchema;
use Dedoc\Scramble\Tests\Fixtures\Laravel\Models\SamplePostModel;
use Illuminate\Http\Resources\Json\JsonResource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Snapshots\MatchesSnapshots;

final class TypeToSchemaTransformerTest extends SymfonyTestCase
{
    use MatchesSnapshots;

    private OpenApiContext $context;
    private array $config = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = [];
        $this->context = new OpenApiContext(new OpenApi('3.1.0'), new GeneratorConfig);
    }

    /**
     * Set a configuration value for the current test.
     */
    private function setConfig(string $key, mixed $value): void
    {
        $this->config[$key] = $value;
        // Update the context with new config
        $this->context = new OpenApiContext(
            new OpenApi('3.1.0'),
            (new GeneratorConfig)->useConfig($this->config)
        );
    }

    /**
     * Get a configuration value.
     */
    private function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    #[Test]
    #[DataProvider('simpleTypesProvider')]
    public function transformsSimpleTypes($type, $openApiArrayed): void
    {
        $transformer = new TypeTransformer(
            $this->getContainer()->get(Infer::class),
            $this->context
        );

        $this->assertSame(json_encode($openApiArrayed), json_encode($transformer->transform($type)->toArray()));
    }

    public static function simpleTypesProvider(): array
    {
        return [
            [new IntegerType, ['type' => 'integer']],
            [new StringType, ['type' => 'string']],
            [new LiteralStringType('wow'), ['type' => 'string', 'enum' => ['wow']]],
            [new LiteralFloatType(157.50), ['type' => 'number', 'enum' => [157.5]]],
            [new BooleanType, ['type' => 'boolean']],
            [new MixedType, (object) []],
            [new ArrayType(value: new StringType), ['type' => 'array', 'items' => ['type' => 'string']]],
            [new KeyedArrayType([
                new ArrayItemType_('key', new IntegerType),
                new ArrayItemType_('optional_key', new IntegerType, true),
            ]), [
                'type' => 'object',
                'properties' => [
                    'key' => ['type' => 'integer'],
                    'optional_key' => ['type' => 'integer'],
                ],
                'required' => ['key'],
            ]],
            [new KeyedArrayType([
                new ArrayItemType_(null, new IntegerType),
                new ArrayItemType_(null, new IntegerType),
                new ArrayItemType_(null, new IntegerType),
            ]), [
                'type' => 'array',
                'prefixItems' => [
                    ['type' => 'integer'],
                    ['type' => 'integer'],
                    ['type' => 'integer'],
                ],
                'minItems' => 3,
                'maxItems' => 3,
                'additionalItems' => false,
            ]],
        ];
    }

    #[Test]
    public function getsJsonResourceType(): void
    {
        $infer = $this->getContainer()->get(Infer::class);
        $transformer = new TypeTransformer($infer, $this->context, [JsonResourceTypeToSchema::class]);
        $extension = new JsonResourceTypeToSchema($infer, $transformer, $this->context->openApi->components, $this->context);

        $type = new ObjectType(ComplexTypeHandlersTest_SampleType::class);

        $this->assertMatchesSnapshot($extension->toSchema($type)->toArray());
    }

    #[Test]
    public function getsEnumWithValuesType(): void
    {
        $infer = $this->getContainer()->get(Infer::class);
        $transformer = new TypeTransformer($infer, $this->context, [EnumToSchema::class]);
        $extension = new EnumToSchema($infer, $transformer, $this->context->openApi->components, $this->context);

        $type = new ObjectType(StatusTwo::class);

        $this->assertMatchesSnapshot($extension->toSchema($type)->toArray());
    }

    #[Test]
    public function getsEnumWithValuesTypeAndDescription(): void
    {
        $this->setConfig('enum_cases_description_strategy', 'description');

        $infer = $this->getContainer()->get(Infer::class);
        $transformer = new TypeTransformer($infer, $this->context, [EnumToSchema::class]);
        $extension = new EnumToSchema($infer, $transformer, $this->context->openApi->components);

        $type = new ObjectType(StatusThree::class);

        $this->assertSame(<<<'EOF'
| |
|---|
| `draft` <br/> Drafts are the posts that are not visible by visitors. |
| `published` <br/> Published posts are visible to visitors. |
| `archived` <br/> Archived posts are not visible to visitors. |
EOF, $extension->toSchema($type)->toArray()['description']);
    }

    #[Test]
    public function getsEnumWithItsDescriptionAndCasesDescription(): void
    {
        $this->setConfig('enum_cases_description_strategy', 'description');

        $infer = $this->getContainer()->get(Infer::class);
        $transformer = new TypeTransformer($infer, $this->context, [EnumToSchema::class]);
        $extension = new EnumToSchema($infer, $transformer, $this->context->openApi->components);

        $type = new ObjectType(StatusFour::class);

        $this->assertSame(<<<'EOF'
Description for StatusFour.
| |
|---|
| `draft` <br/> Drafts are the posts that are not visible by visitors. |
EOF, $extension->toSchema($type)->toArray()['description']);
    }

    #[Test]
    public function preservesEnumCasesDescriptionButOverridesTheEnumSchemaDescriptionWhenUsedInObject(): void
    {
        $this->setConfig('enum_cases_description_strategy', 'description');

        $infer = $this->getContainer()->get(Infer::class);
        $transformer = new TypeTransformer($infer, $this->context, [EnumToSchema::class]);

        $type = $this->getStatementType(<<<'EOF'
[
    /**
     * Override for StatusFour.
     * @var StatusFour
     */
    'a' => unknown(),
]
EOF);

        $this->assertSame(<<<'EOF'
Override for StatusFour.
| |
|---|
| `draft` <br/> Drafts are the posts that are not visible by visitors. |
EOF, $transformer->transform($type)->toArray()['properties']['a']['description']);
    }

    #[Test]
    public function getsEnumWithValuesTypeAndDescriptionWithExtensions(): void
    {
        $this->setConfig('enum_cases_description_strategy', 'extension');

        $infer = $this->getContainer()->get(Infer::class);
        $transformer = new TypeTransformer($infer, $this->context, [EnumToSchema::class]);
        $extension = new EnumToSchema($infer, $transformer, $this->context->openApi->components);

        $type = new ObjectType(StatusThree::class);

        $this->assertEquals([
            'draft' => 'Drafts are the posts that are not visible by visitors.',
            'published' => 'Published posts are visible to visitors.',
            'archived' => 'Archived posts are not visible to visitors.',
        ], $extension->toSchema($type)->toArray()['x-enumDescriptions']);
    }

    #[Test]
    public function getsEnumWithValuesTypeAndNamesAsVarnamesWithExtensions(): void
    {
        $this->setConfig('enum_cases_names_strategy', 'varnames');

        $infer = $this->getContainer()->get(Infer::class);
        $transformer = new TypeTransformer($infer, $this->context, [EnumToSchema::class]);
        $extension = new EnumToSchema($infer, $transformer, $this->context->openApi->components);

        $type = new ObjectType(InvalidEnumValues::class);

        $this->assertEquals([
            'PLUS',
            'MINUS',
            'ONE',
        ], $extension->toSchema($type)->toArray()['x-enum-varnames']);
    }

    #[Test]
    public function getsEnumWithValuesTypeWithoutNamesWithExtensions(): void
    {
        $this->setConfig('enum_cases_names_strategy', false);

        $infer = $this->getContainer()->get(Infer::class);
        $transformer = new TypeTransformer($infer, $this->context, [EnumToSchema::class]);
        $extension = new EnumToSchema($infer, $transformer, $this->context->openApi->components);

        $type = new ObjectType(InvalidEnumValues::class);

        $keys = array_keys($extension->toSchema($type)->toArray());
        $this->assertNotContains('x-enumNames', $keys);
        $this->assertNotContains('x-enum-varnames', $keys);
    }

    #[Test]
    public function getsEnumWithValuesTypeAndNamesAsEnumNamesWithExtensions(): void
    {
        $this->setConfig('enum_cases_names_strategy', 'names');

        $infer = $this->getContainer()->get(Infer::class);
        $transformer = new TypeTransformer($infer, $this->context, [EnumToSchema::class]);
        $extension = new EnumToSchema($infer, $transformer, $this->context->openApi->components);

        $type = new ObjectType(InvalidEnumValues::class);

        $this->assertEquals([
            'PLUS',
            'MINUS',
            'ONE',
        ], $extension->toSchema($type)->toArray()['x-enumNames']);
    }

    #[Test]
    public function getsEnumWithValuesTypeAndDescriptionWithoutCases(): void
    {
        $this->setConfig('enum_cases_description_strategy', false);

        $infer = $this->getContainer()->get(Infer::class);
        $transformer = new TypeTransformer($infer, $this->context, [EnumToSchema::class]);
        $extension = new EnumToSchema($infer, $transformer, $this->context->openApi->components);

        $type = new ObjectType(StatusThree::class);

        $this->assertEquals([
            'type' => 'string',
            'enum' => ['draft', 'published', 'archived'],
        ], $extension->toSchema($type)->toArray());
    }

    #[Test]
    public function getsJsonResourceTypeWithNestedMerges(): void
    {
        $infer = $this->getContainer()->get(Infer::class);
        $transformer = new TypeTransformer($infer, $this->context, [JsonResourceTypeToSchema::class]);
        $extension = new JsonResourceTypeToSchema($infer, $transformer, $this->context->openApi->components, $this->context);

        $type = new ObjectType(ComplexTypeHandlersWithNestedTest_SampleType::class);

        $this->assertMatchesSnapshot($extension->toSchema($type)->toArray());
    }

    #[Test]
    public function getsJsonResourceTypeWithWhen(): void
    {
        $infer = $this->getContainer()->get(Infer::class);
        $transformer = new TypeTransformer($infer, $this->context, [JsonResourceTypeToSchema::class]);
        $extension = new JsonResourceTypeToSchema($infer, $transformer, $this->context->openApi->components, $this->context);

        $type = new ObjectType(ComplexTypeHandlersWithWhen_SampleType::class);

        $this->assertMatchesSnapshot($extension->toSchema($type)->toArray());
    }

    #[Test]
    public function getsJsonResourceTypeWithWhenLoaded(): void
    {
        $infer = $this->getContainer()->get(Infer::class);
        $transformer = new TypeTransformer($infer, $this->context, [
            JsonResourceTypeToSchema::class,
            AnonymousResourceCollectionTypeToSchema::class,
        ]);
        $extension = new JsonResourceTypeToSchema($infer, $transformer, $this->context->openApi->components, $this->context);

        $type = new ObjectType(ComplexTypeHandlersWithWhenLoaded_SampleType::class);

        $this->assertMatchesSnapshot($extension->toSchema($type)->toArray());
    }

    #[Test]
    public function getsJsonResourceTypeWithWhenCounted(): void
    {
        $infer = $this->getContainer()->get(Infer::class);
        $transformer = new TypeTransformer($infer, $this->context, [
            JsonResourceTypeToSchema::class,
            AnonymousResourceCollectionTypeToSchema::class,
        ]);
        $extension = new JsonResourceTypeToSchema($infer, $transformer, $this->context->openApi->components, $this->context);

        $type = new ObjectType(ComplexTypeHandlersWithWhenCounted_SampleType::class);

        $this->assertMatchesSnapshot($extension->toSchema($type)->toArray());
    }

    #[Test]
    public function getsJsonResourceTypeReference(): void
    {
        $infer = $this->getContainer()->get(Infer::class);
        $transformer = new TypeTransformer($infer, $this->context, [JsonResourceTypeToSchema::class]);

        $type = new ObjectType(ComplexTypeHandlersTest_SampleType::class);

        $this->assertEquals([
            '$ref' => '#/components/schemas/ComplexTypeHandlersTest_SampleType',
        ], $transformer->transform($type)->toArray());

        $this->assertMatchesSnapshot($this->context->openApi->components->getSchema(ComplexTypeHandlersTest_SampleType::class)->toArray());
    }

    #[Test]
    public function getsNullableTypeReference(): void
    {
        $infer = $this->getContainer()->get(Infer::class);
        $transformer = new TypeTransformer($infer, $this->context, [JsonResourceTypeToSchema::class]);

        $type = new Union([
            new ObjectType(ComplexTypeHandlersTest_SampleType::class),
            new NullType,
        ]);

        $this->assertEquals([
            'anyOf' => [
                ['$ref' => '#/components/schemas/ComplexTypeHandlersTest_SampleType'],
                ['type' => 'null'],
            ],
        ], $transformer->transform($type)->toArray());
    }

    #[Test]
    public function infersDateColumnDirectlyReferencedInJsonAsDateTime(): void
    {
        $infer = $this->getContainer()->get(Infer::class);
        $transformer = new TypeTransformer($infer, $this->context, [JsonResourceTypeToSchema::class]);

        $type = new ObjectType(InferTypesTest_JsonResourceWithCarbonAttribute::class);

        $this->assertEquals([
            '$ref' => '#/components/schemas/InferTypesTest_JsonResourceWithCarbonAttribute',
        ], $transformer->transform($type)->toArray());

        $schema = $this->context->openApi->components->getSchema(InferTypesTest_JsonResourceWithCarbonAttribute::class)->toArray();

        $this->assertEquals([
            'type' => ['string', 'null'],
            'format' => 'date-time',
        ], $schema['properties']['created_at']);

        $this->assertEquals([
            'type' => 'string',
            'format' => 'date-time',
        ], $schema['properties']['deleted_at']);

        $this->assertEquals([
            'id',
            'created_at',
            'updated_at',
        ], $schema['required']);
    }

    #[Test]
    public function supportsExampleTagInApiResource(): void
    {
        $infer = $this->getContainer()->get(Infer::class);
        $transformer = new TypeTransformer($infer, $this->context, [JsonResourceTypeToSchema::class]);

        $type = new ObjectType(ApiResourceTest_ResourceWithExamples::class);

        $this->assertEquals([
            '$ref' => '#/components/schemas/ApiResourceTest_ResourceWithExamples',
        ], $transformer->transform($type)->toArray());

        $this->assertEquals([
            'type' => 'integer',
            'examples' => [
                'Foo',
                'Multiword example',
            ],
        ], $this->context->openApi->components->getSchema(ApiResourceTest_ResourceWithExamples::class)->toArray()['properties']['id']);
    }

    #[Test]
    public function supportsFormatTagInApiResource(): void
    {
        $infer = $this->getContainer()->get(Infer::class);
        $transformer = new TypeTransformer($infer, $this->context, [JsonResourceTypeToSchema::class]);

        $type = new ObjectType(ApiResourceTest_ResourceWithFormat::class);

        $this->assertEquals([
            '$ref' => '#/components/schemas/ApiResourceTest_ResourceWithFormat',
        ], $transformer->transform($type)->toArray());

        $this->assertEquals([
            'type' => 'string',
            'format' => 'date-time',
        ], $this->context->openApi->components->getSchema(ApiResourceTest_ResourceWithFormat::class)->toArray()['properties']['now']);
    }

    #[Test]
    public function supportsSimpleCommentsDescriptionsInApiResource(): void
    {
        $infer = $this->getContainer()->get(Infer::class);
        $transformer = new TypeTransformer($infer, $this->context, [JsonResourceTypeToSchema::class]);

        $type = new ObjectType(ApiResourceTest_ResourceWithSimpleDescription::class);

        $this->assertEquals([
            '$ref' => '#/components/schemas/ApiResourceTest_ResourceWithSimpleDescription',
        ], $transformer->transform($type)->toArray());

        $this->assertEquals([
            'type' => 'string',
            'format' => 'date-time',
            'description' => 'The date of the current moment.',
        ], $this->context->openApi->components->getSchema(ApiResourceTest_ResourceWithSimpleDescription::class)->toArray()['properties']['now']);

        $this->assertEquals([
            'type' => 'string',
            'format' => 'date-time',
            'description' => 'Inline comments are also supported.',
        ], $this->context->openApi->components->getSchema(ApiResourceTest_ResourceWithSimpleDescription::class)->toArray()['properties']['now2']);
    }

    #[Test]
    public function supportsListTypes(): void
    {
        $infer = $this->getContainer()->get(Infer::class);
        $transformer = new TypeTransformer($infer, $this->context, [JsonResourceTypeToSchema::class]);

        $type = new ObjectType(ApiResourceTest_ResourceWithList::class);

        $schema = $transformer->transform($type)->toArray();
        $component = $this->context->openApi->components->getSchema(ApiResourceTest_ResourceWithList::class)->toArray();

        $this->assertEquals([
            '$ref' => '#/components/schemas/ApiResourceTest_ResourceWithList',
        ], $schema);

        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'integer',
                    ],
                ],
            ],
            'required' => ['items'],
        ], $component);
    }

    #[Test]
    public function supportsIntegers(): void
    {
        $infer = $this->getContainer()->get(Infer::class);
        $transformer = new TypeTransformer($infer, $this->context, [JsonResourceTypeToSchema::class]);

        $type = new ObjectType(ApiResourceTest_ResourceWithIntegers::class);

        $schema = $transformer->transform($type)->toArray();
        $component = $this->context->openApi->components->getSchema(ApiResourceTest_ResourceWithIntegers::class)->toArray();

        $this->assertEquals([
            '$ref' => '#/components/schemas/ApiResourceTest_ResourceWithIntegers',
        ], $schema);

        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'zero' => [
                    'type' => 'integer',
                ],
                'one' => [
                    'type' => 'integer',
                    'minimum' => 1,
                ],
                'minus_one' => [
                    'type' => 'integer',
                    'maximum' => -1,
                ],
                'minus_two' => [
                    'type' => 'integer',
                    'maximum' => 0,
                ],
                'two' => [
                    'type' => 'integer',
                    'minimum' => 0,
                ],
                'three' => [
                    'type' => 'integer',
                ],
                'four' => [
                    'type' => 'integer',
                    'minimum' => 4,
                    'maximum' => 5,
                ],
                'four_max' => [
                    'type' => 'integer',
                    'minimum' => 4,
                ],
                'min_to_five' => [
                    'type' => 'integer',
                    'maximum' => 5,
                ],
                'max_to_five' => [
                    'type' => 'integer',
                ],
                'five_to_min' => [
                    'type' => 'integer',
                ],
            ],
            'required' => [
                'zero',
                'one',
                'minus_one',
                'minus_two',
                'two',
                'three',
                'four',
                'four_max',
                'min_to_five',
                'max_to_five',
                'five_to_min',
            ],
        ], $component);
    }
}

class ComplexTypeHandlersTest_SampleType extends JsonResource
{
    public function toArray($request)
    {
        return [
            'foo' => 1,
            $this->mergeWhen(true, [
                'hey' => 'ho',
            ]),
            $this->merge([
                'bar' => 'foo',
            ]),
        ];
    }
}

class ComplexTypeHandlersWithNestedTest_SampleType extends JsonResource
{
    public function toArray($request)
    {
        return [
            'foo' => 1,
            'wait' => [
                'one' => 1,
                $this->merge([
                    'bar' => 'foo',
                    'kek' => [
                        $this->merge([
                            'bar' => 'foo',
                        ]),
                    ],
                ]),
            ],
            $this->mergeWhen(true, [
                'hey' => 'ho',
            ]),
            $this->merge([
                'bar' => 'foo',
            ]),
        ];
    }
}

class ComplexTypeHandlersWithWhen_SampleType extends JsonResource
{
    public function toArray($request)
    {
        return [
            'foo' => $this->when(true, fn() => 1),
            'bar' => $this->when(true, fn() => 'b', null),
        ];
    }
}

class ComplexTypeHandlersWithWhenLoaded_SampleType extends JsonResource
{
    public function toArray($request)
    {
        return [
            'opt_foo_new' => new ComplexTypeHandlersWithWhen_SampleType($this->whenLoaded('foo')),
            'opt_foo_make' => ComplexTypeHandlersWithWhen_SampleType::make($this->whenLoaded('foo')),
            'opt_foo_collection' => ComplexTypeHandlersWithWhen_SampleType::collection($this->whenLoaded('foo')),
            'foo_new' => new ComplexTypeHandlersWithWhen_SampleType($this->foo),
            'foo_make' => ComplexTypeHandlersWithWhen_SampleType::make($this->foo),
            'foo_collection' => ComplexTypeHandlersWithWhen_SampleType::collection($this->foo),
            'bar' => $this->whenLoaded('bar', fn() => 1),
            'bar_nullable' => $this->whenLoaded('bar', fn() => 's', null),
        ];
    }
}

class ComplexTypeHandlersWithWhenCounted_SampleType extends JsonResource
{
    public function toArray($request)
    {
        return [
            'bar_single' => $this->whenCounted('bar'),
            'bar_fake_count' => $this->whenCounted('bar', 1),
            'bar_different_literal_types' => $this->whenCounted('bar', fn() => 1, 5),
            'bar_identical_literal_types' => $this->whenCounted('bar', fn() => 1, 1),
            'bar_string' => $this->whenCounted('bar', fn() => '2'),
            'bar_int' => $this->whenCounted('bar', fn() => 1),
            'bar_useless' => $this->whenCounted('bar', null),
            'bar_nullable' => $this->whenCounted('bar', fn() => 3, null),
        ];
    }
}

/**
 * @property SamplePostModel $resource
 */
class InferTypesTest_JsonResourceWithCarbonAttribute extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->whenNotNull($this->deleted_at),
        ];
    }
}

/**
 * @property SamplePostModel $resource
 */
class ApiResourceTest_ResourceWithExamples extends JsonResource
{
    public function toArray($request)
    {
        return [
            /**
             * @example Foo
             * @example Multiword example
             */
            'id' => $this->id,
        ];
    }
}

class ApiResourceTest_ResourceWithList extends JsonResource
{
    public function toArray($request)
    {
        return [
            /** @var list<int> $items */
            'items' => $this->resource->items,
        ];
    }
}

/**
 * @property SamplePostModel $resource
 */
class ApiResourceTest_ResourceWithFormat extends JsonResource
{
    public function toArray($request)
    {
        return [
            /**
             * @var string $now
             *
             * @format date-time
             */
            'now' => now(),
        ];
    }
}

class ApiResourceTest_ResourceWithSimpleDescription extends JsonResource
{
    public function toArray($request)
    {
        return [
            /**
             * The date of the current moment.
             */
            'now' => now(),
            // Inline comments are also supported.
            'now2' => now(),
        ];
    }
}

class ApiResourceTest_ResourceWithIntegers extends JsonResource
{
    public function toArray($request)
    {
        return [
            /** @var int */
            'zero' => 0,
            /** @var positive-int */
            'one' => 1,
            /** @var negative-int */
            'minus_one' => -1,
            /** @var non-positive-int */
            'minus_two' => -2,
            /** @var non-negative-int */
            'two' => 2,
            /** @var non-zero-int */
            'three' => 3,
            /** @var int<4, 5> */
            'four' => 4,
            /** @var int<4, max> */
            'four_max' => 4,
            /** @var int<min, 5> */
            'min_to_five' => 4,
            /** @var int<max, 5> */
            'max_to_five' => 4,
            /** @var int<5, min> */
            'five_to_min' => 4,
        ];
    }
}

enum StatusTwo: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
}

enum StatusThree: string
{
/**
     * Drafts are the posts that are not visible by visitors.
     */
    case DRAFT = 'draft';
/**
     * Published posts are visible to visitors.
     */
    case PUBLISHED = 'published';
/**
     * Archived posts are not visible to visitors.
     */
    case ARCHIVED = 'archived';
}

/**
 * Description for StatusFour.
 */
enum StatusFour: string
{
/**
     * Drafts are the posts that are not visible by visitors.
     */
    case DRAFT = 'draft';
}

enum InvalidEnumValues: string
{
    case PLUS = '+';
    case MINUS = '-';
    case ONE = '1';
}
