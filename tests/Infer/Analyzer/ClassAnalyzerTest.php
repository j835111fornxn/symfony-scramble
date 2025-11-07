<?php

namespace Dedoc\Scramble\Tests\Infer\Analyzer;

use Dedoc\Scramble\Infer\Analyzer\ClassAnalyzer;
use Dedoc\Scramble\Infer\Scope\Index;
use Dedoc\Scramble\Infer\Scope\NodeTypesResolver;
use Dedoc\Scramble\Infer\Scope\Scope;
use Dedoc\Scramble\Infer\Scope\ScopeContext;
use Dedoc\Scramble\Infer\Services\ReferenceTypeResolver;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Tests\Infer\stubs\Child;
use Dedoc\Scramble\Tests\Infer\stubs\ChildParentSetterCalls;
use Dedoc\Scramble\Tests\Infer\stubs\ChildPromotion;
use Dedoc\Scramble\Tests\Infer\stubs\DeepChild;
use Dedoc\Scramble\Tests\Infer\stubs\Foo;
use Dedoc\Scramble\Tests\Infer\stubs\FooWithDefaultProperties;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ClassAnalyzerTest extends SymfonyTestCase
{
    private Index $index;
    private ClassAnalyzer $classAnalyzer;
    private ReferenceTypeResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->index = $this->getContainer()->get(Index::class);
        $this->classAnalyzer = new ClassAnalyzer($this->index);
        $this->resolver = new ReferenceTypeResolver($this->index);
    }

    #[Test]
    public function resolvesReturnTypeAfterExplicitlyRequested(): void
    {
        $fooDef = $this->classAnalyzer
            ->analyze(Foo::class)
            ->getMethodDefinition('bar');

        $this->assertSame('int(243)', $fooDef->type->getReturnType()->toString());
    }

    #[Test]
    public function resolvesFullyQualifiedNames(): void
    {
        $fqnDef = $this->classAnalyzer
            ->analyze(Foo::class)
            ->getMethodDefinition('fqn');

        $this->assertSame('class-string<'.Foo::class.'>', $fqnDef->type->getReturnType()->toString());
    }

    #[Test]
    public function describesConstructorArgumentAssignmentsAsSelfOutType(): void
    {
        $constructor = $this->classAnalyzer
            ->analyze(ConstructorArgumentAssignment_ClassAnalyzerTest::class)
            ->getMethodDefinition('__construct');

        $this->assertNotNull($constructor->selfOutTypeBuilder);
        $this->assertSame('self<array{a: TFoo1}, _>', $constructor->getSelfOutType()->toString());
    }

    #[Test]
    public function describesDirectConstructorArgumentAssignmentsAsTemplatePlaceholders(): void
    {
        $constructor = $this->classAnalyzer
            ->analyze(ConstructorDirectArgumentAssignment_ClassAnalyzerTest::class)
            ->getMethodDefinition('__construct');

        $this->assertNotNull($constructor->selfOutTypeBuilder);
        $this->assertSame('self<_>', $constructor->getSelfOutType()->toString());
    }

    #[Test]
    public function describesArgumentsAssignmentsAsSelfOutTypeOnAnyMethod(): void
    {
        $method = $this->classAnalyzer
            ->analyze(MethodArgumentAssignment_ClassAnalyzerTest::class)
            ->getMethodDefinition('setFoo');

        $this->assertNotNull($method->selfOutTypeBuilder);
        $this->assertSame('self<array{foo: TSomething}>', $method->getSelfOutType()->toString());
    }

    #[Test]
    public function describesArgumentsPassedToParentConstructorCallAsPartOfSelfOutType(): void
    {
        $constructor = $this->classAnalyzer
            ->analyze(ParentConstructorCall_ClassAnalyzerTest::class)
            ->getMethodDefinition('__construct');

        $this->assertNotNull($constructor->selfOutTypeBuilder);
        $this->assertSame('self<array{a: int(42)}, _>', $constructor->getSelfOutType()->toString());
    }

    #[Test]
    public function describesArgumentsPassedToDirectParentConstructorCallAsPartOfSelfOutType(): void
    {
        $constructor = $this->classAnalyzer
            ->analyze(DirectParentConstructorCall_ClassAnalyzerTest::class)
            ->getMethodDefinition('__construct');

        $this->assertNotNull($constructor->selfOutTypeBuilder);
        $this->assertSame('self<int(42), _>', $constructor->getSelfOutType()->toString());
    }

    #[Test]
    public function describesPropertiesSetInSettersAsPartOfSelfOut(): void
    {
        $constructor = $this->classAnalyzer
            ->analyze(SetterCall_ClassAnalyzerTest::class)
            ->getMethodDefinition('__construct');

        $this->assertNotNull($constructor->selfOutTypeBuilder);
        $this->assertSame('self<TB>', $constructor->getSelfOutType()->toString());
    }

    #[Test]
    public function describesPropertiesSetInFluentSettersAsPartOfSelfOut(): void
    {
        $constructor = $this->classAnalyzer
            ->analyze(FluentSetterCall_ClassAnalyzerTest::class)
            ->getMethodDefinition('__construct');

        $this->assertNotNull($constructor->selfOutTypeBuilder);
        $this->assertSame('self<TB, int(42)>', $constructor->getSelfOutType()->toString());
    }

    #[Test]
    public function describesPropertiesSetInFluentSettersSetOfVariablesAsPartOfSelfOut(): void
    {
        $constructor = $this->classAnalyzer
            ->analyze(FluentSetterOnVariablesCall_ClassAnalyzerTest::class)
            ->getMethodDefinition('__construct');

        $this->assertNotNull($constructor->selfOutTypeBuilder);
        $this->assertSame('self<TB, int(42)>', $constructor->getSelfOutType()->toString());
    }

    #[Test]
    public function resolvesPendingReturnsLazily(): void
    {
        $classDefinition = $this->classAnalyzer->analyze(Foo::class);

        $barDef = $classDefinition->getMethodDefinition('bar');
        $barReturnType = $this->resolver->resolve(
            new Scope($this->index, new NodeTypesResolver, new ScopeContext($classDefinition), new \Dedoc\Scramble\Infer\Services\FileNameResolver(new \PhpParser\NameContext(new \PhpParser\ErrorHandler\Throwing))),
            $barDef->type->getReturnType(),
        );

        $this->assertSame('int(243)', $barReturnType->toString());
    }

    #[Test]
    public function preservesCommentsInPropertyDefaults(): void
    {
        $classDef = $this->classAnalyzer->analyze(FooWithDefaultProperties::class);

        /** @var KeyedArrayType $defaultType */
        $defaultType = $classDef->properties['default']->defaultType;

        $this->assertInstanceOf(KeyedArrayType::class, $defaultType);
        $this->assertNotNull($defaultType->items[0]->getAttribute('docNode'));
        $this->assertNotNull($defaultType->items[1]->getAttribute('docNode'));
    }

    #[Test]
    public function analyzesParentInstantiation(): void
    {
        $this->classAnalyzer->analyze(Child::class);

        $type = $this->getStatementType('new Dedoc\Scramble\Tests\Infer\stubs\Child("some", "wow", 42)');

        $this->assertSame('Dedoc\Scramble\Tests\Infer\stubs\Child<int(42), string(wow), string(some)>', $type->toString());
    }

    #[Test]
    public function analyzesDeepParentInstantiation(): void
    {
        $this->classAnalyzer->analyze(DeepChild::class);

        $type = $this->getStatementType('new Dedoc\Scramble\Tests\Infer\stubs\DeepChild("some", "wow", 42)');

        $this->assertSame('Dedoc\Scramble\Tests\Infer\stubs\DeepChild<int(42), string(wow), string(some)>', $type->toString());
    }

    #[Test]
    public function analyzesParentWithPropertyPromotion(): void
    {
        $this->classAnalyzer->analyze(ChildPromotion::class);

        $type = $this->getStatementType('new Dedoc\Scramble\Tests\Infer\stubs\ChildPromotion("some", "wow", 42)');

        $this->assertSame('Dedoc\Scramble\Tests\Infer\stubs\ChildPromotion<int(42), string(wow), string(some)>', $type->toString());
    }

    #[Test]
    public function analyzesCallToParentSetterMethodsInChildConstructor(): void
    {
        $this->classAnalyzer->analyze(ChildParentSetterCalls::class);

        $type = $this->getStatementType('new Dedoc\Scramble\Tests\Infer\stubs\ChildParentSetterCalls("some", "wow")');

        $this->assertSame('Dedoc\Scramble\Tests\Infer\stubs\ChildParentSetterCalls<string(from ChildParentSetterCalls constructor), string(from ChildParentSetterCalls wow), string(some)>', $type->toString());
    }

    #[Test]
    public function analyzesFluentSettersCalledInConstructor(): void
    {
        $this->classAnalyzer->analyze(Foo_ClassAnalyzerTest::class);

        $type = $this->getStatementType('new Foo_ClassAnalyzerTest()');

        $this->assertSame('Foo_ClassAnalyzerTest<int(42), string(baz)>', $type->toString());
    }

    #[Test]
    public function analyzesNotFluentSettersCalledInConstructor(): void
    {
        $this->classAnalyzer->analyze(FooNotFluent_ClassAnalyzerTest::class);

        $type = $this->getStatementType('new FooNotFluent_ClassAnalyzerTest()');

        $this->assertSame('FooNotFluent_ClassAnalyzerTest<int(42), string(baz)>', $type->toString());
    }

    #[Test]
    public function analyzesStaticMethodCallOnClassConstants(): void
    {
        $this->classAnalyzer->analyze(ConstFetchStaticCallChild_ClassAnalyzerTest::class);

        $type = $this->getStatementType('(new ConstFetchStaticCallChild_ClassAnalyzerTest)->staticMethodCall()');

        $this->assertSame('int(42)', $type->toString());
    }

    #[Test]
    public function analyzesNewCallOnClassConstants(): void
    {
        $this->classAnalyzer->analyze(ConstFetchStaticCallChild_ClassAnalyzerTest::class);

        $type = $this->getStatementType('(new ConstFetchStaticCallChild_ClassAnalyzerTest)->newCall()');

        $this->assertSame('ConstFetchStaticCallFoo_ClassAnalyzerTest', $type->toString());
    }

    #[Test]
    public function analyzesCallOnTypedProperties(): void
    {
        $this->classAnalyzer->analyze(InjectedProperty_ClassAnalyzerTest::class);

        $type = $this->getStatementType('(new InjectedProperty_ClassAnalyzerTest)->bar()');

        $this->assertSame('int(42)', $type->toString());
    }
}

// Test fixture classes

class ConstructorArgumentAssignment_ClassAnalyzerTest
{
    public $foo;

    public $bar;

    public function __construct(int $foo)
    {
        $this->foo = ['a' => $foo];
    }
}

class ConstructorDirectArgumentAssignment_ClassAnalyzerTest
{
    public $foo;

    public function __construct(int $foo)
    {
        $this->foo = $foo;
    }
}

class MethodArgumentAssignment_ClassAnalyzerTest
{
    public $foo;

    public function setFoo(int $something)
    {
        $this->foo = ['foo' => $something];
    }
}

class ParentConstructorCall_ClassAnalyzerTest extends ParentConstructorCallee_ClassAnalyzerTest
{
    public $bar;

    public function __construct(int $b)
    {
        parent::__construct(42);
        $this->bar = $b;
    }
}

class ParentConstructorCallee_ClassAnalyzerTest
{
    public $foo;

    public function __construct(int $foo)
    {
        $this->foo = ['a' => $foo];
    }
}

class DirectParentConstructorCall_ClassAnalyzerTest extends DirectParentConstructorCallee_ClassAnalyzerTest
{
    public $bar;

    public function __construct(int $b)
    {
        parent::__construct(42);
        $this->bar = $b;
    }
}

class DirectParentConstructorCallee_ClassAnalyzerTest
{
    public $foo;

    public function __construct(int $foo)
    {
        $this->foo = $foo;
    }
}

class SetterCall_ClassAnalyzerTest
{
    public $bar;

    public function __construct(int $b)
    {
        $this->setBar($b);
    }

    public function setBar(int $b)
    {
        $this->bar = $b;
    }
}

class FluentSetterCall_ClassAnalyzerTest
{
    public $bar;

    public $foo;

    public function __construct(int $b)
    {
        $this->setBar($b)->setFoo(42);
    }

    public function setFoo(int $f)
    {
        $this->foo = $f;

        return $this;
    }

    public function setBar(int $b)
    {
        $this->bar = $b;

        return $this;
    }
}

class FluentSetterOnVariablesCall_ClassAnalyzerTest
{
    public $bar;

    public $foo;

    public function __construct(int $b)
    {
        $a = $this;
        $c = $a->setBar($b);
        $c->setFoo(42);
    }

    public function setFoo(int $f)
    {
        $this->foo = $f;

        return $this;
    }

    public function setBar(int $b)
    {
        $this->bar = $b;

        return $this;
    }
}

class Playground_ClassAnalyzerTest
{
    public $foo;

    public function __construct(int $foo)
    {
        $this->foo = 12;
    }
}

class Foo_ClassAnalyzerTest
{
    public int $foo;

    public string $bar;

    public function __construct()
    {
        $this
            ->setFoo(42)
            ->setBar('baz');
    }

    public function setFoo($number)
    {
        $this->foo = $number;

        return $this;
    }

    public function setBar($string)
    {
        $this->bar = $string;

        return $this;
    }
}

class FooNotFluent_ClassAnalyzerTest
{
    public int $foo;

    public string $bar;

    public function __construct()
    {
        $this->setFoo(42);
        $this->setBar('baz');
    }

    public function setFoo($number)
    {
        $this->foo = $number;
    }

    public function setBar($string)
    {
        $this->bar = $string;
    }
}

class ConstFetchStaticCallParent_ClassAnalyzerTest
{
    public function staticMethodCall()
    {
        return (static::FOO_CLASS)::foo();
    }

    public function newCall()
    {
        return new (static::FOO_CLASS);
    }
}

class ConstFetchStaticCallChild_ClassAnalyzerTest extends ConstFetchStaticCallParent_ClassAnalyzerTest
{
    public const FOO_CLASS = ConstFetchStaticCallFoo_ClassAnalyzerTest::class;
}

class ConstFetchStaticCallFoo_ClassAnalyzerTest
{
    public static function foo()
    {
        return 42;
    }
}

class InjectedProperty_ClassAnalyzerTest
{
    public function __construct(private FooProperty_ClassAnalyzerTest $fooProp) {}

    public function bar()
    {
        return $this->fooProp->foo();
    }
}

class FooProperty_ClassAnalyzerTest
{
    public function foo()
    {
        return 42;
    }
}
