<?php

namespace Dedoc\Scramble\Tests\Infer\Scope;

use Dedoc\Scramble\Infer\Definition\ClassDefinition;
use Dedoc\Scramble\Infer\Definition\FunctionLikeDefinition;
use Dedoc\Scramble\Infer\Scope\GlobalScope;
use Dedoc\Scramble\Infer\Scope\Index;
use Dedoc\Scramble\Infer\Services\ReferenceTypeResolver;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Collection;
use Dedoc\Scramble\Support\Type\FunctionType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\IntegerType;
use Dedoc\Scramble\Support\Type\Reference\MethodCallReferenceType;
use Dedoc\Scramble\Support\Type\StringType;
use Dedoc\Scramble\Tests\Support\AnalysisHelpers;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\Test;

final class IndexTest extends SymfonyTestCase
{
    use AnalysisHelpers;

    private Index $index;

    protected function setUp(): void
    {
        parent::setUp();
        $this->index = new Index;
    }

    #[Test]
    public function doesntFailOnInternalClassDefinitionRequest(): void
    {
        $def = $this->index->getClass(\Error::class);

        $this->assertInstanceOf(ClassDefinition::class, $def);
    }

    #[Test]
    public function retrievesFunctionDefinitions(): void
    {
        $def = $this->index->getFunction('is_null');

        $this->assertInstanceOf(FunctionLikeDefinition::class, $def);
    }
}

// Test fixture classes for reflection-based tests
class Bar_IndexTest
{
    public function foo(): int {}
}

class Foo_IndexTest extends Bar_IndexTest {}

// Additional test class for IndexTest_Reflection
final class IndexTest_Reflection extends SymfonyTestCase
{
    use AnalysisHelpers;

    private Index $index;

    protected function setUp(): void
    {
        parent::setUp();

        Scramble::infer()
            ->configure()
            ->buildDefinitionsUsingReflectionFor([
                Bar_IndexTest::class,
                BarGeneric_IndexTest::class,
            ]);

        $this->index = new Index;
    }

    #[Test]
    public function canGetPrimitiveTypeFromNonAstAnalyzableClass(): void
    {
        $type = $this->index->getClass(Foo_IndexTest::class)->getMethod('foo')->getReturnType();

        $this->assertSame('int', $type->toString());
    }

    #[Test]
    public function canGetTemplateTypeFromNonAstAnalyzableClass(): void
    {
        Scramble::infer()
            ->configure()
            ->buildDefinitionsUsingReflectionFor([
                Template_IndexTest::class,
            ]);

        $type = $this->index->getClass(Template_IndexTest::class)->getMethod('foo')->type->toString();

        $this->assertSame('<T>(T): T', $type);
    }

    #[Test]
    public function canGetGenericTypeFromNonAstAnalyzableClass(): void
    {
        $type = ReferenceTypeResolver::getInstance()
            ->resolve(
                new GlobalScope,
                new MethodCallReferenceType(
                    new Generic(BarGeneric_IndexTest::class, [new StringType]),
                    'foo',
                    [],
                ),
            );

        $this->assertSame('string', $type->toString());
    }

    #[Test]
    public function canGetGenericTypeFromExtendedNonAstAnalyzableClass(): void
    {
        $type = $this->index->getClass(FooGeneric_IndexTest::class)
            ->getMethod('foo')
            ->getReturnType();

        $this->assertSame('string', $type->toString());
    }

    #[Test]
    public function buildsClassDefinitionWithMixinWithoutTrait(): void
    {
        $type = $this->index->getClass(FooMixin_IndexTest::class)
            ->getMethod('foo')
            ->getReturnType();

        $this->assertSame('int', $type->toString());
    }

    #[Test]
    public function buildsClassDefinitionWithMixinWithTrait(): void
    {
        $type = $this->index->getClass(FooTrait_IndexTest::class)
            ->getMethod('foo')
            ->getReturnType();

        $this->assertSame('boolean', $type->toString());
    }

    #[Test]
    public function buildsClassDefinitionWithMixinWithGenericTrait(): void
    {
        Scramble::infer()
            ->configure()
            ->buildDefinitionsUsingReflectionFor([
                BarTGeneric_IndexTest::class,
            ]);

        $type = $this->index->getClass(FooTraitGeneric_IndexTest::class)
            ->getMethod('foo')
            ->getReturnType();

        $this->assertSame('int(42)', $type->toString());
    }

    #[Test]
    public function properlyStoresTemplatesInDefinitions(): void
    {
        Scramble::infer()
            ->configure()
            ->buildDefinitionsUsingReflectionFor([
                T_IndexTest::class,
                TParent_IndexTest::class,
            ]);

        $definition = $this->index->getClass(T_IndexTest::class);

        $this->assertCount(1, $definition->templateTypes);
        $this->assertSame('T', $definition->templateTypes[0]->name);
    }

    #[Test]
    public function handlesDeepContextWithMixin(): void
    {
        Scramble::infer()
            ->configure()
            ->buildDefinitionsUsingReflectionFor([
                BazParent_IndexTest::class,
                BazTrait_IndexTest::class,
                Baz_IndexTest::class,
            ]);

        $definition = $this->index->getClass(Baz_IndexTest::class);

        $this->assertSame('int', $definition->getMethod('foo')->getReturnType()->toString());
    }

    #[Test]
    public function handlesDeepContextWithMixedInClass(): void
    {
        Scramble::infer()
            ->configure()
            ->buildDefinitionsUsingReflectionFor([
                BazUsesClass_IndexTest::class,
                BazClass_IndexTest::class,
            ]);

        $definition = $this->index->getClass(BazUsesClass_IndexTest::class);

        $this->assertSame('int', $definition->getMethod('foo')->getReturnType()->toString());
    }

    #[Test]
    public function handlesDeepContextWithUse(): void
    {
        Scramble::infer()
            ->configure()
            ->buildDefinitionsUsingReflectionFor([
                BazUseParent_IndexTest::class,
                BazTrait_IndexTest::class,
                BazUse_IndexTest::class,
            ]);

        $definition = $this->index->getClass(BazUse_IndexTest::class);

        $this->assertSame('int', $definition->getMethod('foo')->getReturnType()->toString());
    }

    #[Test]
    public function infersComplexTypeFromFlatMap(): void
    {
        $collectionType = new Generic(Collection::class, [
            new IntegerType,
            new StringType,
        ]);
        $type = ReferenceTypeResolver::getInstance()->resolve(
            new GlobalScope,
            new MethodCallReferenceType($collectionType, 'flatMap', [
                new FunctionType('{}', [], new Generic(Collection::class, [
                    new IntegerType,
                    new IntegerType,
                ])),
            ]),
        );

        $this->assertSame(Collection::class.'<int, int>', $type->toString());
    }

    #[Test]
    public function handlesCollectionGetCall(): void
    {
        $type = $this->getStatementType('(new '.Collection::class.'())->get(1, fn () => 1)');

        $this->assertSame('unknown|int(1)', $type->toString());
    }

    #[Test]
    public function handlesCollectionFirstCall(): void
    {
        $type = ReferenceTypeResolver::getInstance()->resolve(
            new GlobalScope,
            new MethodCallReferenceType(new Generic(Collection::class, [new IntegerType, new IntegerType]), 'first', [new FunctionType('{}', [], new IntegerType)]),
        );

        $this->assertSame('int|null', $type->toString());
    }

    #[Test]
    public function handlesCollectionMapCall(): void
    {
        $type = $this->getStatementType('(new '.Collection::class.'())->map(fn () => 1)');

        $this->assertSame('Illuminate\Support\Collection<int|string, int(1)>', $type->toString());
    }

    #[Test]
    public function handlesCollectionEmptyConstructCall(): void
    {
        $type = $this->getStatementType('(new '.Collection::class.'([]))');

        $this->assertSame('Illuminate\Support\Collection<int|string, unknown>', $type->toString());
    }

    #[Test]
    public function handlesCollectionConstructCall(): void
    {
        $type = $this->getStatementType('(new '.Collection::class.'([42]))');

        $this->assertSame('Illuminate\Support\Collection<int, int(42)>', $type->toString());
    }

    #[Test]
    public function handlesCollectionMapCallWithUndefinedType(): void
    {
        $type = $this->getStatementType('(new '.Collection::class.'([["a" => 42]]))->map(fn ($v) => $v["a"])');

        $this->assertSame('Illuminate\Support\Collection<int, int(42)>', $type->toString());
    }

    #[Test]
    public function handlesCollectionMapCallWithPrimitiveType(): void
    {
        $type = $this->getStatementType('(new '.Collection::class.'([["a" => 42]]))->map(fn (int $v) => $v)');

        $this->assertSame('Illuminate\Support\Collection<int, int>', $type->toString());
    }

    #[Test]
    public function handlesCollectionKeysCall(): void
    {
        $type = $this->getStatementType('(new '.Collection::class.'(["foo" => "bar"]))');

        $this->assertSame('Illuminate\Support\Collection<string(foo), string(bar)>', $type->toString());
    }

    #[Test]
    public function handlesClassDefinitionLogicWhenClassIsAliasAndMixin(): void
    {
        Scramble::infer()->configure()->buildDefinitionsUsingReflectionFor([
            'TheAliasForAliased_IndexTest',
            Aliased_IndexTest::class,
        ]);

        $def = $this->index
            ->getClass(Aliased_IndexTest::class)
            ->getMethod('count');

        $this->assertNotNull($def);
    }
}

// Test fixture classes
class Template_IndexTest
{
    /**
     * @template T
     *
     * @param  T  $a
     * @return T
     */
    public function foo($a) {}
}

/** @template T */
class BarGeneric_IndexTest
{
    /** @return T */
    public function foo() {}
}

/** @extends BarGeneric_IndexTest<string> */
class FooGeneric_IndexTest extends BarGeneric_IndexTest {}

/**
 * @mixin Bar_IndexTest
 */
class FooMixin_IndexTest {}

trait BarT_IndexTest
{
    public function foo(): bool {}
}

class FooTrait_IndexTest
{
    use BarT_IndexTest;
}

/** @template T */
trait BarTGeneric_IndexTest
{
    /** @return T */
    public function foo() {}
}

class FooTraitGeneric_IndexTest
{
    /** @use BarTGeneric_IndexTest<42> */
    use BarTGeneric_IndexTest;
}

/**
 * @template T
 *
 * @extends TParent_IndexTest<T>
 */
class T_IndexTest extends TParent_IndexTest {}

/** @template T */
class TParent_IndexTest {}

/** @mixin BazTrait_IndexTest<int> */
class BazParent_IndexTest {}

/** @template T */
trait BazTrait_IndexTest
{
    /** @return T */
    public function foo() {}
}

class Baz_IndexTest extends BazParent_IndexTest {}

/** @mixin BazClass_IndexTest<int> */
class BazUsesClass_IndexTest {}

/** @template T */
class BazClass_IndexTest
{
    /** @return T */
    public function foo() {}
}

/** @template T */
class BazUseParent_IndexTest
{
    /** @use BazTrait_IndexTest<T> */
    use BazTrait_IndexTest;
}

/** @extends BazUseParent_IndexTest<int> */
class BazUse_IndexTest extends BazUseParent_IndexTest {}

/**
 * @mixin \TheAliasForAliased_IndexTest
 */
class Aliased_IndexTest
{
    public function count() {}
}
class_alias(Aliased_IndexTest::class, 'TheAliasForAliased_IndexTest');
