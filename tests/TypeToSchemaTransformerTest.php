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
use Dedoc\Scramble\Tests\Files\SamplePostModel;
use Illuminate\Http\Resources\Json\JsonResource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Snapshots\MatchesSnapshots;

final class TypeToSchemaTransformerTest extends SymfonyTestCase
{
    use MatchesSnapshots;

    private OpenApiContext $context;

    protected function setUp(): void
    {
        parent::setUp();
        $this->context = new OpenApiContext(new OpenApi('3.1.0'), new GeneratorConfig);
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
            'foo' => $this->when(true, fn () => 1),
            'bar' => $this->when(true, fn () => 'b', null),
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
            'bar' => $this->whenLoaded('bar', fn () => 1),
            'bar_nullable' => $this->whenLoaded('bar', fn () => 's', null),
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
            'bar_different_literal_types' => $this->whenCounted('bar', fn () => 1, 5),
            'bar_identical_literal_types' => $this->whenCounted('bar', fn () => 1, 1),
            'bar_string' => $this->whenCounted('bar', fn () => '2'),
            'bar_int' => $this->whenCounted('bar', fn () => 1),
            'bar_useless' => $this->whenCounted('bar', null),
            'bar_nullable' => $this->whenCounted('bar', fn () => 3, null),
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
