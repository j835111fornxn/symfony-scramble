<?php

namespace Dedoc\Scramble\Tests\InferExtensions;

use Dedoc\Scramble\Infer\Analyzer\ClassAnalyzer;
use Dedoc\Scramble\Infer\Scope\Index;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Collection;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class JsonResourceInferenceTest extends SymfonyTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Scramble::infer()
            ->configure()
            ->buildDefinitionsUsingReflectionFor([
                Bar_JsonResourceInferenceTest::class,
            ]);
    }

    #[Test]
    #[DataProvider('jsonResourceCreationProvider')]
    public function infersJsonResourceCreation(string $expression, string $expectedType): void
    {
        $this->assertSame($expectedType, $this->getStatementType($expression)->toString());
    }

    public static function jsonResourceCreationProvider(): array
    {
        return [
            [
                'new '.Foo_JsonResourceInferenceTest::class.'(42)',
                Foo_JsonResourceInferenceTest::class.'<int(42)>',
            ],
            [
                '(new '.Foo_JsonResourceInferenceTest::class.'(42))->additional(1)',
                Foo_JsonResourceInferenceTest::class.'<int(42), int(1)>',
            ],
            [
                '(new '.ManualConstructCall_JsonResourceInferenceTest::class.'(42))',
                ManualConstructCall_JsonResourceInferenceTest::class.'<int(42)>',
            ],
            [
                '(new '.ManualConstructCallWithData_JsonResourceInferenceTest::class.'(42))',
                ManualConstructCallWithData_JsonResourceInferenceTest::class.'<int(23)>',
            ],
        ];
    }

    #[Test]
    #[DataProvider('staticCollectionCreationProvider')]
    public function infersStaticCollectionCreation(string $expression, string $expectedType): void
    {
        $this->assertSame($expectedType, $this->getStatementType($expression)->toString());
    }

    public static function staticCollectionCreationProvider(): array
    {
        return [
            [
                Foo_JsonResourceInferenceTest::class.'::newCollection([])',
                AnonymousResourceCollection::class.'<list{}, array<mixed>, '.Foo_JsonResourceInferenceTest::class.'>',
            ],
            [
                Foo_JsonResourceInferenceTest::class.'::collection([])',
                AnonymousResourceCollection::class.'<list{}, array<mixed>, '.Foo_JsonResourceInferenceTest::class.'>',
            ],
            [
                OverridenNewCollection_JsonResourceInferenceTest::class.'::collection([])',
                NoCollectedResourcCollection_JsonResourceInferenceTest::class.'<list{}>',
            ],
        ];
    }

    #[Test]
    #[DataProvider('resourceCollectionCreationProvider')]
    public function infersResourceCollectionCreation(string $expression, string $expectedType): void
    {
        $this->assertSame($expectedType, $this->getStatementType($expression)->toString());
    }

    public static function resourceCollectionCreationProvider(): array
    {
        return [
            [
                'new '.NoCollectedResourcCollection_JsonResourceInferenceTest::class.'([])',
                NoCollectedResourcCollection_JsonResourceInferenceTest::class.'<list{}>',
            ],
            [
                'new '.FooCollection_JsonResourceInferenceTest::class.'([])',
                FooCollection_JsonResourceInferenceTest::class.'<list{}, array<mixed>, '.Foo_JsonResourceInferenceTest::class.'>',
            ],
        ];
    }

    #[Test]
    #[DataProvider('resourceCollectionToArrayProvider')]
    public function infersResourceCollectionToArray(string $expression, string $expectedType): void
    {
        $this->assertSame($expectedType, $this->getStatementType($expression)->toString());
    }

    public static function resourceCollectionToArrayProvider(): array
    {
        return [
            // collection with $collects, without toArray
            [
                '(new '.FooCollection_JsonResourceInferenceTest::class.'([]))->toArray()',
                'array<'.Foo_JsonResourceInferenceTest::class.'>',
            ],
            // collection with $collects, with parent::toArray()
            [
                '(new '.ParentToArrayCollection_JsonResourceInferenceTest::class.'([]))->toArray()',
                'array<'.Foo_JsonResourceInferenceTest::class.'>',
            ],
            // collection with $collects, with call to `collection` property
            [
                '(new '.CallToCollection_JsonResourceInferenceTest::class.'([]))->toArray()',
                'array{data: '.Collection::class.'<int, '.Foo_JsonResourceInferenceTest::class.'>, links: array{self: string(link-value)}}',
            ],
        ];
    }

    #[Test]
    public function givesUnderstanding(): void
    {
        $cd = (new ClassAnalyzer($this->getContainer()->get(Index::class)))->analyze(ParentToArrayCollection_JsonResourceInferenceTest::class);

        $md = $cd->getMethod('toArray');

        $this->assertSame('array<TCollects>', $md->type->getReturnType()->toString());
    }

    #[Test]
    #[DataProvider('anonymousCollectionCreationProvider')]
    public function infersAnonymousCollectionCreation(string $expression, string $expectedType): void
    {
        $this->assertSame($expectedType, $this->getStatementType($expression)->toString());
    }

    public static function anonymousCollectionCreationProvider(): array
    {
        return [
            [
                'new '.AnonymousResourceCollection::class.'([], '.Bar_JsonResourceInferenceTest::class.'::class)',
                AnonymousResourceCollection::class.'<list{}, array<mixed>, '.Bar_JsonResourceInferenceTest::class.'>',
            ],
            [
                FooAnonCollection_JsonResourceInferenceTest::class.'::collection([])',
                AnonymousResourceCollection::class.'<list{}, array<mixed>, '.FooAnonCollection_JsonResourceInferenceTest::class.'>',
            ],
            [
                FooAnonCollectionTap_JsonResourceInferenceTest::class.'::collection([])',
                AnonymousResourceCollection::class.'<list{}, array<mixed>, '.FooAnonCollectionTap_JsonResourceInferenceTest::class.'>',
            ],
        ];
    }

    #[Test]
    #[DataProvider('anonymousResourceCollectionToArrayProvider')]
    public function infersAnonymousResourceCollectionToArray(string $expression, string $expectedType): void
    {
        $this->assertSame($expectedType, $this->getStatementType($expression)->toString());
    }

    public static function anonymousResourceCollectionToArrayProvider(): array
    {
        return [
            [
                Foo_JsonResourceInferenceTest::class.'::collection([])->toArray()',
                'array<'.Foo_JsonResourceInferenceTest::class.'>',
            ],
        ];
    }

    #[Test]
    #[DataProvider('fallbackToParentResourceCollectionToArrayProvider')]
    public function fallsBackToParentResourceCollectionToArrayIfCannotInferOverwrittenType(string $expression, string $expectedType): void
    {
        $this->assertSame($expectedType, $this->getStatementType($expression)->toString());
    }

    public static function fallbackToParentResourceCollectionToArrayProvider(): array
    {
        return [
            [
                '(new '.OverwrittenUnknownCollection_JsonResourceInferenceTest::class.'([], '.Foo_JsonResourceInferenceTest::class.'::class))->toArray()',
                'array<'.Foo_JsonResourceInferenceTest::class.'<unknown>>',
            ],
        ];
    }

    #[Test]
    public function handlesThatWeirdCase(): void
    {
        $ca = new ClassAnalyzer($this->getContainer()->get(Index::class));

        $b = $ca->analyze(BaloobooFooAnonCollectionTap_JsonResourceInferenceTest::class);
        $c = $ca->analyze(CarFooAnonCollectionTap_JsonResourceInferenceTest::class);

        $cd = $c->getMethodDefinition('get');
        $bd = $b->getMethodDefinition('get');

        $rt1 = $cd->type->getReturnType();
        $rt2 = $bd->type->getReturnType();

        $this->assertSame('class-string<'.CarFooAnonCollectionTap_JsonResourceInferenceTest::class.'>', $rt1->toString());
        $this->assertSame('class-string<'.BaloobooFooAnonCollectionTap_JsonResourceInferenceTest::class.'>', $rt2->toString());
    }

    #[Test]
    public function handlesThatSecondWeirdCase(): void
    {
        $ca = new ClassAnalyzer($this->getContainer()->get(Index::class));

        $b = $ca->analyze(Bar_JsonResourceInferenceTest::class);
        $j = $ca->analyze(Jar_JsonResourceInferenceTest::class);

        $bget = $b->getMethodDefinition('collection');
        $jget = $j->getMethodDefinition('collection');

        $bgetret = $bget->type->getReturnType();
        $jgetret = $jget->type->getReturnType();

        $this->assertSame(
            AnonymousResourceCollection::class.'<TResource1, array<mixed>, '.Bar_JsonResourceInferenceTest::class.'>',
            $bgetret->toString()
        );
        $this->assertSame(
            AnonymousResourceCollection::class.'<TResource1, array<mixed>, '.Jar_JsonResourceInferenceTest::class.'>',
            $jgetret->toString()
        );
    }
}

// Test fixture classes

class Bar_JsonResourceInferenceTest extends \Illuminate\Http\Resources\Json\JsonResource {}

class Foo_JsonResourceInferenceTest extends Bar_JsonResourceInferenceTest {}

class ManualConstructCall_JsonResourceInferenceTest extends \Illuminate\Http\Resources\Json\JsonResource
{
    public function __construct($resource)
    {
        parent::__construct($resource);
    }
}

class ManualConstructCallWithData_JsonResourceInferenceTest extends \Illuminate\Http\Resources\Json\JsonResource
{
    public function __construct($resource)
    {
        parent::__construct(23);
    }
}

class OverridenNewCollection_JsonResourceInferenceTest extends JsonResource
{
    public static function newCollection($resource)
    {
        return new NoCollectedResourcCollection_JsonResourceInferenceTest($resource);
    }
}

class NoCollectedResourcCollection_JsonResourceInferenceTest extends \Illuminate\Http\Resources\Json\ResourceCollection {}

class FooCollection_JsonResourceInferenceTest extends \Illuminate\Http\Resources\Json\ResourceCollection
{
    public $collects = Foo_JsonResourceInferenceTest::class;
}

class ParentToArrayCollection_JsonResourceInferenceTest extends \Illuminate\Http\Resources\Json\ResourceCollection
{
    public $collects = Foo_JsonResourceInferenceTest::class;

    public function toArray(Request $request)
    {
        return parent::toArray($request);
    }
}

class CallToCollection_JsonResourceInferenceTest extends \Illuminate\Http\Resources\Json\ResourceCollection
{
    public $collects = Foo_JsonResourceInferenceTest::class;

    public function toArray(Request $request)
    {
        return [
            'data' => $this->collection,
            'links' => [
                'self' => 'link-value',
            ],
        ];
    }
}

class OverwrittenUnknownCollection_JsonResourceInferenceTest extends \Illuminate\Http\Resources\Json\AnonymousResourceCollection
{
    public function toArray($request)
    {
        return unknown();
    }
}

class FooAnonCollection_JsonResourceInferenceTest extends \Illuminate\Http\Resources\Json\JsonResource
{
    public static function collection($resource)
    {
        return new AnonymousResourceCollection($resource, static::class);
    }
}

class FooAnonCollectionTap_JsonResourceInferenceTest extends \Illuminate\Http\Resources\Json\JsonResource
{
    public static function collection($resource)
    {
        return tap(new AnonymousResourceCollection($resource, static::class), function ($v) {});
    }
}

class Static_JsonResourceInferenceTest
{
    public static function get()
    {
        return static::class;
    }
}

class BaloobooFooAnonCollectionTap_JsonResourceInferenceTest extends Static_JsonResourceInferenceTest {}

class CarFooAnonCollectionTap_JsonResourceInferenceTest extends Static_JsonResourceInferenceTest {}

class Jar_JsonResourceInferenceTest extends \Illuminate\Http\Resources\Json\JsonResource {}
