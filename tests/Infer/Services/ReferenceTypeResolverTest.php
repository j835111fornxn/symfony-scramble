<?php

namespace Dedoc\Scramble\Tests\Infer\Services;

use Dedoc\Scramble\Infer\Analyzer\ClassAnalyzer;
use Dedoc\Scramble\Infer\Definition\FunctionLikeDefinition;
use Dedoc\Scramble\Infer\DefinitionBuilders\FunctionLikeAstDefinitionBuilder;
use Dedoc\Scramble\Infer\Scope\GlobalScope;
use Dedoc\Scramble\Infer\Scope\Index;
use Dedoc\Scramble\Infer\Services\ReferenceTypeResolver;
use Dedoc\Scramble\Support\Type\AbstractType;
use Dedoc\Scramble\Support\Type\Contracts\LateResolvingType;
use Dedoc\Scramble\Support\Type\FunctionType;
use Dedoc\Scramble\Support\Type\Literal\LiteralStringType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Reference\CallableCallReferenceType;
use Dedoc\Scramble\Support\Type\StringType;
use Dedoc\Scramble\Support\Type\TemplateType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class ReferenceTypeResolverTest extends SymfonyTestCase
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

    /*
     * New calls
     */
    #[Test]
    #[DataProvider('newCallsOnParentClassProvider')]
    public function infersNewCallsOnParentClass(string $method, string $expectedType): void
    {
        $methodDef = $this->classAnalyzer
            ->analyze(\Dedoc\Scramble\Tests\Infer\Services\StaticCallsClasses\Foo::class)
            ->getMethodDefinition($method);

        $this->assertSame($expectedType, $methodDef->type->getReturnType()->toString());
    }

    public static function newCallsOnParentClassProvider(): array
    {
        return [
            ['newSelfCall', 'Dedoc\Scramble\Tests\Infer\Services\StaticCallsClasses\Foo'],
            ['newStaticCall', 'Dedoc\Scramble\Tests\Infer\Services\StaticCallsClasses\Foo'],
        ];
    }

    #[Test]
    #[DataProvider('newCallsOnChildClassProvider')]
    public function infersNewCallsOnChildClass(string $method, string $expectedType): void
    {
        $methodDef = $this->classAnalyzer
            ->analyze(\Dedoc\Scramble\Tests\Infer\Services\StaticCallsClasses\Bar::class)
            ->getMethodDefinition($method);

        $this->assertSame($expectedType, $methodDef->type->getReturnType()->toString());
    }

    public static function newCallsOnChildClassProvider(): array
    {
        return [
            ['newSelfCall', 'Dedoc\Scramble\Tests\Infer\Services\StaticCallsClasses\Foo'],
            ['newStaticCall', 'Dedoc\Scramble\Tests\Infer\Services\StaticCallsClasses\Bar<string(foo)>'],
            ['newParentCall', 'Dedoc\Scramble\Tests\Infer\Services\StaticCallsClasses\Foo'],
        ];
    }

    /*
     * Static method calls (should work the same for both static and non-static methods)
     */
    #[Test]
    #[DataProvider('staticMethodCallsOnParentClassProvider')]
    public function infersStaticMethodCallsOnParentClass(string $method, string $expectedType): void
    {
        $methodDef = $this->classAnalyzer
            ->analyze(\Dedoc\Scramble\Tests\Infer\Services\StaticCallsClasses\Foo::class)
            ->getMethodDefinition($method);

        $this->assertSame($expectedType, $methodDef->type->getReturnType()->toString());
    }

    public static function staticMethodCallsOnParentClassProvider(): array
    {
        return [
            ['selfMethodCall', 'string(foo)'],
            ['staticMethodCall', 'string(foo)'],
        ];
    }

    #[Test]
    #[DataProvider('staticMethodCallsOnChildClassProvider')]
    public function infersStaticMethodCallsOnChildClass(string $method, string $expectedType): void
    {
        $methodDef = $this->classAnalyzer
            ->analyze(\Dedoc\Scramble\Tests\Infer\Services\StaticCallsClasses\Bar::class)
            ->getMethodDefinition($method);

        $this->assertSame($expectedType, $methodDef->type->getReturnType()->toString());
    }

    public static function staticMethodCallsOnChildClassProvider(): array
    {
        return [
            ['selfMethodCall', 'string(foo)'],
            ['staticMethodCall', 'string(bar)'],
            ['parentMethodCall', 'string(foo)'],
        ];
    }

    #[Test]
    #[DataProvider('staticClassFetchOnParentProvider')]
    public function infersStaticClassFetchOnParent(string $method, string $expectedType): void
    {
        $methodDef = $this->classAnalyzer
            ->analyze(\Dedoc\Scramble\Tests\Infer\Services\StaticCallsClasses\Foo::class)
            ->getMethodDefinition($method);

        $this->assertSame($expectedType, $methodDef->type->getReturnType()->toString());
    }

    public static function staticClassFetchOnParentProvider(): array
    {
        return [
            ['staticClassFetch', 'class-string<Dedoc\Scramble\Tests\Infer\Services\StaticCallsClasses\Foo>'],
        ];
    }

    #[Test]
    #[DataProvider('staticClassFetchOnChildProvider')]
    public function infersStaticClassFetchOnChild(string $method, string $expectedType): void
    {
        $methodDef = $this->classAnalyzer
            ->analyze(\Dedoc\Scramble\Tests\Infer\Services\StaticCallsClasses\Bar::class)
            ->getMethodDefinition($method);

        $this->assertSame($expectedType, $methodDef->type->getReturnType()->toString());
    }

    public static function staticClassFetchOnChildProvider(): array
    {
        return [
            ['staticClassFetch', 'class-string<Dedoc\Scramble\Tests\Infer\Services\StaticCallsClasses\Bar>'],
        ];
    }

    #[Test]
    #[DataProvider('staticClassFetchOnChildWhenCalledFromOutsideProvider')]
    public function infersStaticClassFetchOnChildWhenCalledFromOutside(string $method, string $expectedType): void
    {
        $methodDef = $this->classAnalyzer
            ->analyze(CallRef_ReferenceTypeResolverTest::class)
            ->getMethodDefinition($method);

        $this->assertSame($expectedType, $methodDef->type->getReturnType()->toString());
    }

    public static function staticClassFetchOnChildWhenCalledFromOutsideProvider(): array
    {
        return [
            ['baz', 'class-string<Dedoc\Scramble\Tests\Infer\Services\StaticCallsClasses\Bar>'],
        ];
    }

    #[Test]
    public function complexStaticCallAndPropertyFetch(): void
    {
        $type = $this->getStatementType('Dedoc\Scramble\Tests\Infer\Services\StaticCallsClasses\Bar::wow()');

        $this->assertSame('string(foo)', $type->toString());
    }

    /*
     * Static method calls
     */
    #[Test]
    public function infersStaticMethodCallType(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public static function foo ($a) {
        return $a;
    }
}
EOD)->getExpressionType("Foo::foo('wow')");

        $this->assertSame('string(wow)', $type->toString());
    }

    #[Test]
    public function infersStaticMethodCallTypeWithNamedArgs(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public static function foo ($a) {
        return $a;
    }
}
EOD)->getExpressionType("Foo::foo(a: 'wow')");

        $this->assertSame('string(wow)', $type->toString());
    }

    #[Test]
    public function infersStaticMethodCallTypeWithNamedUnpackedArgs(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public static function foo ($a) {
        return $a;
    }
}
EOD)->getExpressionType("Foo::foo(...['a' => 'wow'])");

        $this->assertSame('string(wow)', $type->toString());
    }

    /*
     * Ability to override accepted by type and track annotated types
     */
    #[Test]
    public function allowsOverridingTypesAcceptedByAnotherType(): void
    {
        $functionType = new FunctionType(
            'wow',
            returnType: $expectedReturnType = new class('sample') extends ObjectType
            {
                public function acceptedBy(Type $otherType): bool
                {
                    return $otherType instanceof StringType;
                }
            },
        );
        $functionType->setAttribute(
            'annotatedReturnType',
            new StringType,
        );

        $def = new FunctionLikeDefinition($functionType);

        FunctionLikeAstDefinitionBuilder::resolveFunctionReturnReferences(
            new GlobalScope,
            $def,
        );

        $actualReturnType = $functionType->getReturnType();

        $this->assertInstanceOf(ObjectType::class, $actualReturnType);
        $this->assertSame($expectedReturnType->name, $actualReturnType->name);
    }

    #[Test]
    public function resolvesOnlyArgumentsWithTemplatesReferencedInReturnType(): void
    {
        $templates = [$t = new TemplateType('T')];
        $fn = tap(new FunctionType(
            '_',
            arguments: ['foo' => $t],
            returnType: new LiteralStringType('wow'),
        ), fn ($f) => $f->templates = $templates);

        $result = ReferenceTypeResolver::getInstance()->resolve(
            new GlobalScope,
            new CallableCallReferenceType($fn, [
                new class extends AbstractType implements LateResolvingType
                {
                    public function resolve(): Type
                    {
                        throw new LogicException('should not happen');
                    }

                    public function isResolvable(): bool
                    {
                        return true;
                    }

                    public function isSame(Type $type)
                    {
                        return false;
                    }

                    public function toString(): string
                    {
                        return '__test__';
                    }
                },
            ]),
        );

        $this->assertSame('string(wow)', $result->toString());
    }
}

// Test fixture classes

class CallRef_ReferenceTypeResolverTest
{
    public static function baz()
    {
        return static::foo();
    }

    public static function foo()
    {
        return \Dedoc\Scramble\Tests\Infer\Services\StaticCallsClasses\Bar::staticClassFetch();
    }
}
