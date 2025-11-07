<?php

namespace Dedoc\Scramble\Tests\Infer;

// Tests for resolving references behavior

use Dedoc\Scramble\Infer\Extensions\Event\FunctionCallEvent;
use Dedoc\Scramble\Infer\Extensions\Event\ReferenceResolutionEvent;
use Dedoc\Scramble\Infer\Extensions\FunctionReturnTypeExtension;
use Dedoc\Scramble\Infer\Extensions\ResolvingType;
use Dedoc\Scramble\Infer\Extensions\TypeResolverExtension;
use Dedoc\Scramble\Infer\Scope\GlobalScope;
use Dedoc\Scramble\Infer\Services\ReferenceTypeResolver;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Type;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\UnknownType;
use Dedoc\Scramble\Tests\Infer\stubs\InvokableFoo;
use Dedoc\Scramble\Tests\Support\AnalysisHelpers;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use Dedoc\Scramble\Tests\TestUtils;
use PHPUnit\Framework\Attributes\Test;

final class ReferenceResolutionTest extends SymfonyTestCase
{
    use AnalysisHelpers;

    #[Test]
    public function supportsCreatingAnObjectWithoutConstructor(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public $prop;
}
EOD
        )->getExpressionType('new Foo()');

        $this->assertInstanceOf(Generic::class, $type);
        $this->assertSame('Foo', $type->name);
        $this->assertSame('Foo<unknown>', $type->toString());
        $this->assertCount(1, $type->templateTypes);
        $this->assertInstanceOf(UnknownType::class, $type->templateTypes[0]);
    }

    #[Test]
    public function supportsCreatingAnObjectWithAConstructor(): void
    {
        $type = $this->analyzeFile(__DIR__.'/files/class_with_simple_constructor_and_property.php')
            ->getExpressionType('new Foo(132)');

        $this->assertInstanceOf(Generic::class, $type);
        $this->assertSame('Foo', $type->name);
        $this->assertCount(1, $type->templateTypes);
        $this->assertSame('int(132)', $type->templateTypes[0]->toString());
        $this->assertSame('Foo<int(132)>', $type->toString());
    }

    #[Test]
    public function selfTemplateDefinitionSideEffectWorks(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public $prop;
    public function setProp($a) {
        $this->prop = $a;
        return $this;
    }
}
EOD)->getExpressionType('(new Foo)->setProp(123)');

        $this->assertSame('Foo<int(123)>', $type->toString());
    }

    #[Test]
    public function evaluatesSelfType(): void
    {
        $type = $this->analyzeFile(__DIR__.'/files/class_with_method_that_returns_self.php')
            ->getExpressionType('(new Foo)->foo()');

        $this->assertSame('Foo', $type->toString());
    }

    #[Test]
    public function understandsMethodCallsType(): void
    {
        $type = $this->analyzeFile(__DIR__.'/files/class_with_self_chain_calls_method.php')
            ->getExpressionType('(new Foo)->foo()->foo()->one()');

        $this->assertSame('int(1)', $type->toString());
    }

    #[Test]
    public function understandsTemplatedPropertyFetchTypeValueForPropertyFetch(): void
    {
        $type = $this->analyzeFile(__DIR__.'/files/class_with_property_fetch_in_method.php')
            ->getExpressionType('(new Foo(42))->prop');

        $this->assertSame('int(42)', $type->toString());
    }

    #[Test]
    public function understandsTemplatedPropertyFetchTypeValueForPropertyFetchCalledInMethod(): void
    {
        $type = $this->analyzeFile(__DIR__.'/files/class_with_property_fetch_in_method.php')
            ->getExpressionType('(new Foo(42))->foo()');

        $this->assertSame('int(42)', $type->toString());
    }

    #[Test]
    public function resolvesNestedTemplates(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public $prop;
    public function __construct($prop)
    {
        $this->prop = $prop;
    }
    public function foo($prop, $a) {
        return fn ($prop) => [$this->prop, $prop, $a];
    }
}
EOD)->getExpressionType('(new Foo("wow"))->foo("prop", 42)(12)');

        $this->assertSame('list{string(wow), int(12), int(42)}', $type->toString());
    }

    #[Test]
    public function doesntResolveTemplatesFromNotOwnDefinition(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public $a;
    public $prop;
    public function __construct($a, $prop)
    {
        $this->a = $a;
        $this->prop = $prop;
    }
    public function getProp() {
        return $this->prop;
    }
}
EOD)->getExpressionType('(new Foo(1, fn ($a) => $a))->getProp()');

        $this->assertSame('<TA>(TA): TA', $type->toString());
    }

    #[Test]
    public function resolvesMethodCallFromParentClass(): void
    {
        $type = $this->analyzeClass(Mc_Foo::class)->getExpressionType('(new Mc_Foo)->foo()');

        $this->assertSame('int(2)', $type->toString());
    }

    #[Test]
    public function resolvesCallToParentClass(): void
    {
        $type = $this->analyzeClass(Cp_Foo::class)->getClassDefinition('Cp_Foo');

        $this->assertSame('(): int(2)', $type->getMethodDefinition('foo')->type->toString());
    }

    #[Test]
    public function resolvesPolymorphicCallFromParentClass(): void
    {
        $this->markTestSkipped('is it really that needed?');

        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo extends Bar {
    public function foo () {
        return $this->bar();
    }
    public function two () {
        return 2;
    }
}
class Bar {
    public function bar () {
        return $this->two();
    }
}
EOD)->getClassDefinition('Foo');

        $this->assertSame('(): int(2)', $type->methods['foo']->type->toString());
    }

    #[Test]
    public function detectsParentClassCallsCyclicReference(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo extends Bar {
    public function foo () {
        return $this->bar();
    }
}
class Bar {
    public function bar () {
        return $this->foo();
    }
}
EOD)->getClassDefinition('Foo');

        $this->assertSame('(): unknown', $type->methods['foo']->type->toString());
    }

    #[Test]
    public function detectsIndirectCallsCyclicReference(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public function foo () {
        return $this->bar();
    }
    public function bar () {
        return $this->foo();
    }
}
EOD)->getClassDefinition('Foo');

        $this->assertSame('(): unknown', $type->methods['foo']->type->toString());
    }

    #[Test]
    public function getsPropertyTypeFromParentClassWhenConstructed(): void
    {
        $type = $this->analyzeClass(Pt_Foo::class)
            ->getExpressionType('(new Pt_Foo(2))->foo()');

        $this->assertSame('int(2)', $type->toString());
    }

    #[Test]
    public function collapsesTheSameTypesInUnion(): void
    {
        $type = $this->analyzeClass(SameUnionTypes_Foo::class)
            ->getExpressionType('(new SameUnionTypes_Foo(2))->foo()');

        $this->assertSame('int(1)', $type->toString());
    }

    #[Test]
    public function resolvesInvokableCallFromParentClass(): void
    {
        $type = $this->analyzeClass(InvokableFoo::class)->getExpressionType('(new Dedoc\Scramble\Tests\Infer\stubs\InvokableFoo)("foo")');

        $this->assertSame('string(foo)', $type->toString());
    }

    #[Test]
    public function handlesInvokableCallToClosureTypeWithoutFailing(): void
    {
        $type = $this->getStatementType('(new \Closure)("foo")');

        $this->assertSame('unknown', $type->toString());
    }

    #[Test]
    public function handlesCustomResolvablePhpDocTypes(): void
    {
        Scramble::registerExtension(Pick_ReferenceResolutionTest::class);

        $type = self::getContainer()->get(ReferenceTypeResolver::class)->resolve(
            new GlobalScope,
            new Type\Generic('Pick', [
                new Type\Generic('Pick', [
                    new Type\KeyedArrayType([
                        new Type\ArrayItemType_('a', new Type\IntegerType),
                        new Type\ArrayItemType_('b', new Type\StringType),
                        new Type\ArrayItemType_('c', new Type\IntegerType),
                    ]),
                    Type\Union::wrap([
                        new Type\Literal\LiteralStringType('a'),
                        new Type\Literal\LiteralStringType('b'),
                    ]),
                ]),
                Type\Union::wrap([
                    new Type\Literal\LiteralStringType('a'),
                ]),
            ])
        );

        $this->assertSame('array{a: int}', $type->toString());
    }

    #[Test]
    public function handlesAllTemplatesResolvablePhpDocTypes(): void
    {
        Scramble::registerExtensions([
            AllTemplatesInfer_ReferenceResolutionTest::class,
        ]);

        $type = $this->getStatementType('(new FooAllTemplates_ReferenceResolutionTest)->foo(a: 1, b: 2, c: 3)');

        $this->assertSame('array{a: int(1), b: int(2), c: int(3)}', $type->toString());
    }

    #[Test]
    public function allowsKeepPhpDocTypesResolutionLogicOnCustomType(): void
    {
        $type = TestUtils::parseType('AlwaysInt_ReferenceResolutionTest<string>');

        $resolvedType = ReferenceTypeResolver::getInstance()->resolve(new GlobalScope, $type);

        $this->assertSame('int', $resolvedType->toString());
    }
}

class Mc_Foo extends Mc_Bar {}

class Mc_Bar
{
    public function foo()
    {
        return 2;
    }
}

class Cp_Foo extends Cp_Bar
{
    public function foo()
    {
        return $this->two();
    }
}

class Cp_Bar
{
    public function two()
    {
        return 2;
    }
}

class Pt_Foo extends Pt_Bar
{
    public function foo()
    {
        return $this->barProp;
    }
}

class Pt_Bar
{
    public $barProp;

    public function __construct($b)
    {
        $this->barProp = $b;
    }
}

class SameUnionTypes_Foo
{
    public function foo()
    {
        if (rand()) {
            return $this->bar();
        }

        return $this->car();
    }

    public function bar()
    {
        return 1;
    }

    public function car()
    {
        return 1;
    }
}

class Pick_ReferenceResolutionTest implements TypeResolverExtension
{
    public function resolve(ReferenceResolutionEvent $event): ?Type\Type
    {
        $type = $event->type;

        // $context->emitter
        // $context->arguments
        // $context->scope
        // $context->resolver
        // $context->index

        if (! $type instanceof Type\Generic) {
            return null;
        }

        if ($type->name !== 'Pick') {
            return null;
        }

        if (count($type->templateTypes) !== 2) {
            return null;
            $context->emitter->error('Pick expects 2 type arguments to be passed, got '.count($type->templateTypes));
        }

        [$subject, $keys] = $type->templateTypes;

        if (! $subject instanceof Type\KeyedArrayType) {
            return null;
            $context->emitter->error('Pick expects 2 type arguments to be passed, got '.count($type->templateTypes));
        }

        $isHandleableUnion = $keys instanceof Type\Union
            && count(array_filter($keys->types, fn (Type\Type $t) => $t instanceof Type\Literal\LiteralIntegerType || $t instanceof Type\Literal\LiteralStringType)) === count($keys->types);

        if (! $keys instanceof Type\Literal\LiteralStringType && ! $isHandleableUnion) {
            return null;
            $context->emitter->error('Pick 2-nd type argument must be union of strings or integer literals, got '.$keys->toString());
        }

        $keys = collect($keys instanceof Type\Union ? $keys->types : [$keys])->map->value->all();

        return new Type\KeyedArrayType(collect($subject->items)->filter(fn (Type\ArrayItemType_ $t) => in_array($t->key, $keys))->all());
    }
}

class FooAllTemplates_ReferenceResolutionTest
{
    public function foo()
    {
        return func_get_args();
    }
}

class AllTemplatesInfer_ReferenceResolutionTest implements FunctionReturnTypeExtension
{
    public function shouldHandle(string $name): bool
    {
        return $name === 'func_get_args';
    }

    public function getFunctionReturnType(FunctionCallEvent $event): ?Type\Type
    {
        return new Type\TemplateType('Arguments');
    }
}

class AlwaysInt_ReferenceResolutionTest implements ResolvingType
{
    public function resolve(ReferenceResolutionEvent $event): ?Type\Type
    {
        return new Type\IntegerType;
    }
}
