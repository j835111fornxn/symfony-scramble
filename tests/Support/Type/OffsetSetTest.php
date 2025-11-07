<?php

namespace Dedoc\Scramble\Tests\Support\Type;

use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Tests\Support\TypeInferenceAssertions;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\Test;

final class OffsetSetTest extends SymfonyTestCase
{
    use TypeInferenceAssertions;

    #[Test]
    public function handlesArrayFetch(): void
    {
        $this->assertHasType('int(1)', function () {
            expect(['a' => 1]['a']);
        });
    }

    #[Test]
    public function handlesArraySetType(): void
    {
        $this->assertHasType('array{foo: int(42)}', function () {
            $a = [];
            $a['foo'] = 42;

            expect($a);
        });
    }

    #[Test]
    public function handlesArrayPushType(): void
    {
        $this->assertHasType('list{int(42), int(1)}', function () {
            $a = [];
            $a[] = 42;
            $a[] = 1;

            expect($a);
        });
    }

    #[Test]
    public function handlesArrayModifyType(): void
    {
        $this->assertHasType('array{foo: int(42)}', function () {
            $a = ['foo' => 23];

            $a['foo'] = 42;

            expect($a);
        });
    }

    #[Test]
    public function handlesArrayDeepSetType(): void
    {
        $this->assertHasType('array{foo: array{bar: int(42)}}', function () {
            $a = [];
            $a['foo']['bar'] = 42;

            expect($a);
        });
    }

    #[Test]
    public function handlesArrayDeepModifyType(): void
    {
        $this->assertHasType('array{foo: array{bar: int(42)}}', function () {
            $a = ['foo' => []];
            $a['foo']['bar'] = 42;

            expect($a);
        });
    }

    #[Test]
    public function handlesArrayDeepPushType(): void
    {
        $this->assertHasType('array{foo: array{bar: list{int(42), int(1)}}}', function () {
            $a = ['foo' => []];
            $a['foo']['bar'][] = 42;
            $a['foo']['bar'][] = 1;

            expect($a);
        });
    }

    #[Test]
    public function allowsSettingKeysOnTemplateType(): void
    {
        $this->assertHasType('array{foo: string(bar), wow: int(42)}', function () {
            $a = function ($b) {
                $b['wow'] = 42;

                return $b;
            };

            $wow = ['foo' => 'bar'];
            $wow2 = $a($wow);

            expect($wow2);
        });
    }

    #[Test]
    public function allowsSettingKeysOnTemplateTypeWithDeepMethodsLogic(): void
    {
        $this->markTestSkipped('figure out test ns');

        $this->assertHasType('array{foo: string(bar), a: int(1), b: int(2), c: int(3)}', function () {
            $foo = new Foo_ExpressionsTest;

            $result = $foo->setC(['foo' => 'bar']);

            expect($result);
        });
    }

    #[Test]
    public function allowsCallingMethodsOnRetrievedTypes(): void
    {
        $this->markTestSkipped('figure out test ns');

        $this->assertHasType('int(42)', function () {
            $arr = [];
            $foo = new Foo_ExpressionsTest;

            $arr['foo'] = $foo;

            $r = $arr['foo']->get42();

            expect($r);
        });
    }

    #[Test]
    public function allowsCallingMethodsOnDeepRetrievedTypes(): void
    {
        $this->markTestSkipped('figure out test ns');

        $this->assertHasType('int(42)', function () {
            $arr = [
                'foo' => ['bar' => new Foo_ExpressionsTest],
            ];

            $r = $arr['foo']['bar']->get42();

            expect($r);
        });
    }

    #[Test]
    public function preservesArrayKeyDescriptionWhenSettingTheOffsetFromOffsetGet(): void
    {
        $this->assertHasType(function (Type $t) {
            $openApiTransformer = self::getContainer()->get(TypeTransformer::class);
            $openApiTransformer = new TypeTransformer(
                new OpenApiContext(new OpenApi('3.1.0'), new GeneratorConfig)
            );

            $this->assertSame([
                'type' => 'object',
                'properties' => [
                    'bar' => [
                        'type' => 'integer',
                        'description' => 'Foo description.',
                        'enum' => [42],
                    ],
                ],
                'required' => ['bar'],
            ], $openApiTransformer->transform($t)->toArray());

            return true;
        }, function () {
            $arr = [
                /** Foo description. */
                'foo' => 42,
            ];

            $newArr = [];
            $newArr['bar'] = $arr['foo'];

            expect($newArr);
        });
    }

    #[Test]
    public function preservesArrayKeyDescriptionWhenSettingTheKeyFromOffsetGet(): void
    {
        $this->assertHasType(function (Type $t) {
            $openApiTransformer = self::getContainer()->get(TypeTransformer::class);
            $openApiTransformer = new TypeTransformer(
                new OpenApiContext(new OpenApi('3.1.0'), new GeneratorConfig)
            );

            $this->assertSame([
                'type' => 'object',
                'properties' => [
                    'bar' => [
                        'type' => 'integer',
                        'description' => 'Foo description.',
                        'enum' => [42],
                    ],
                ],
                'required' => ['bar'],
            ], $openApiTransformer->transform($t)->toArray());

            return true;
        }, function () {
            $arr = [
                'bar' => [
                    /** Foo description. */
                    'foo' => 42,
                ],
            ];

            $newArr = [
                'bar' => $arr['bar']['foo'],
            ];

            expect($newArr);
        });
    }
}

class Foo_ExpressionsTest
{
    public function get42()
    {
        return 42;
    }

    public function setA($data)
    {
        $data['a'] = 1;

        return $data;
    }

    public function setB($data)
    {
        $data = $this->setA($data);
        $data['b'] = 2;

        return $data;
    }

    public function setC($data)
    {
        $data = $this->setB($data);
        $data['c'] = 3;

        return $data;
    }
}
