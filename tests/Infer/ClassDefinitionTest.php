<?php

namespace Dedoc\Scramble\Tests\Infer;

// Tests for which definition is created from class' source

use Dedoc\Scramble\Infer\Analyzer\ClassAnalyzer;
use Dedoc\Scramble\Infer\Definition\ClassPropertyDefinition;
use Dedoc\Scramble\Infer\Extensions\AfterClassDefinitionCreatedExtension;
use Dedoc\Scramble\Infer\Extensions\Event\ClassDefinitionCreatedEvent;
use Dedoc\Scramble\Infer\Scope\Index;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Type\GenericClassStringType;
use Dedoc\Scramble\Support\Type\Literal\LiteralIntegerType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\TemplateType;
use Dedoc\Scramble\Support\Type\TypePath;
use Dedoc\Scramble\Tests\Support\AnalysisHelpers;
use Dedoc\Scramble\Tests\Support\DataProviders;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class ClassDefinitionTest extends SymfonyTestCase
{
    use AnalysisHelpers;

    private Index $index;
    private ClassAnalyzer $classAnalyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->index = self::getContainer()->get(Index::class);
        $this->classAnalyzer = new ClassAnalyzer($this->index);
    }

    #[Test]
    public function findsType(): void
    {
        $this->markTestIncomplete('TODO: move to its own test case');

        $type = $this->getStatementType(<<<'EOD'
['a' => fn (int $b) => 123]
EOD);

        $path = TypePath::findFirst(
            $type,
            fn ($t) => $t instanceof LiteralIntegerType,
        );

        $this->assertSame('int(123)', $path?->getFrom($type)->toString());
    }

    #[Test]
    public function infersFromPropertyDefaultType(): void
    {
        Scramble::registerExtension(AfterFoo_ClassDefinitionTest::class);

        $this->classAnalyzer->analyze(Foo_ClassDefinitionTest::class);

        $this->assertSame(
            'Foo_ClassDefinitionTest<Illuminate\Database\Eloquent\Builder>',
            $this->getStatementType('new '.Foo_ClassDefinitionTest::class)->toString()
        );
    }

    #[Test]
    public function infersFromConstructorArgumentType(): void
    {
        Scramble::registerExtension(AfterBar_ClassDefinitionTest::class);

        $this->classAnalyzer->analyze(Bar_ClassDefinitionTest::class);

        $this->assertSame(
            'Bar_ClassDefinitionTest<Dedoc\Scramble\Support\Generator\Schema>',
            $this->getStatementType('new '.Bar_ClassDefinitionTest::class.'(prop: '.\Dedoc\Scramble\Support\Generator\Schema::class.'::class)')->toString()
        );
    }

    #[Test]
    public function classGeneratesDefinition(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {}
EOD)->getClassDefinition('Foo');

        $this->assertSame('Foo', $type->name);
        $this->assertCount(0, $type->templateTypes);
        $this->assertCount(0, $type->properties);
        $this->assertCount(0, $type->methods);
    }

    #[Test]
    public function addsPropertiesAndMethodsToClassDefinition(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public $prop;
    public function foo () {}
}
EOD)->getClassDefinition('Foo');

        $this->assertSame('Foo', $type->name);
        $this->assertCount(1, $type->properties);
        $this->assertArrayHasKey('prop', $type->properties);
        $this->assertCount(1, $type->methods);
        $this->assertArrayHasKey('foo', $type->methods);
    }

    #[Test]
    public function inferPropertiesDefaultTypesFromValues(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public $prop = 42;
}
EOD)->getClassDefinition('Foo');

        $this->assertCount(1, $type->templateTypes);
        $this->assertSame('TProp', $type->properties['prop']->type->toString());
        $this->assertSame('int(42)', $type->properties['prop']->defaultType->toString());
    }

    #[Test]
    #[DataProvider('extendableTemplateTypesProvider')]
    public function infersPropertiesTypesFromTypehints(string $paramType, string $expectedParamType, string $expectedTemplateDefinitionType = ''): void
    {
        $def = $this->analyzeFile("<?php class Foo { public $paramType \$a; }")->getClassDefinition('Foo');

        $this->assertSame($expectedParamType, $def->properties['a']->type->toString());

        if (! $expectedTemplateDefinitionType) {
            $this->assertEmpty($def->templateTypes);
        } else {
            $this->assertSame($expectedTemplateDefinitionType, $def->templateTypes[0]->toDefinitionString());
        }
    }

    #[Test]
    public function settingAParameterToPropertyInConstructorMakesItTemplateType(): void
    {
        $type = $this->analyzeFile(__DIR__.'/files/class_with_simple_constructor_and_property.php')
            ->getClassDefinition('Foo');

        $this->assertCount(1, $type->templateTypes);
        $this->assertSame('TProp', $type->templateTypes[0]->toString());
        $this->assertSame('TProp', $type->properties['prop']->type->toString());
        $this->assertSame('(TProp): void', $type->methods['__construct']->type->toString());
    }

    #[Test]
    public function settingAParameterToPropertyInMethodMakesItLocalMethodTemplateTypeAndDefinesSelfOut(): void
    {
        $def = $this->classAnalyzer->analyze(SetPropToMethod_ClassDefinitionTest::class);

        $this->assertCount(1, $def->templateTypes);
        $this->assertSame('TProp', $def->templateTypes[0]->toString());

        $this->assertSame('TProp', $def->properties['prop']->type->toString());

        $setProp = $def->getMethodDefinition('setProp');

        $this->assertSame('<TA>(TA): void', $setProp->type->toString());
        $this->assertSame('self<TA>', $setProp->getSelfOutType()->toString());
    }

    #[Test]
    public function understandsSelfType(): void
    {
        $type = $this->analyzeFile(__DIR__.'/files/class_with_method_that_returns_self.php')
            ->getClassDefinition('Foo');

        $this->assertSame('(): self', $type->methods['foo']->type->toString());
    }

    #[Test]
    public function understandsMethodCallsType(): void
    {
        $type = $this->analyzeFile(__DIR__.'/files/class_with_self_chain_calls_method.php')
            ->getClassDefinition('Foo');

        $this->assertSame('(): int(1)', $type->methods['bar']->type->toString());
    }

    #[Test]
    public function infersTemplatedPropertyFetchType(): void
    {
        $type = $this->analyzeFile(__DIR__.'/files/class_with_property_fetch_in_method.php')
            ->getClassDefinition('Foo');

        $this->assertSame('(): TProp', $type->methods['foo']->type->toString());
    }

    #[Test]
    public function generatesTemplateTypesWithoutConflicts(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public $prop;
    public function getPropGetter($prop) {
        return fn ($prop, $q) => [$q, $prop, $this->prop];
    }
}
EOD)->getClassDefinition('Foo');

        $this->assertSame(
            '<TProp1>(TProp1): <TProp2, TQ>(TProp2, TQ): list{TQ, TProp2, TProp}',
            $type->methods['getPropGetter']->type->toString()
        );
    }

    #[Test]
    public function generatesDefinitionForInheritance(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo extends Bar {
}
class Bar {
}
EOD)->getClassDefinition('Foo');

        $this->assertSame('Bar', $type->parentFqn);
    }

    #[Test]
    public function generatesDefinitionBasedOnParentWhenAnalyzingInheritance(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo extends Bar {
    public function foo () {
        return $this->barProp;
    }
}
class Bar {
    public $barProp;
    public function __construct($b) {
        $this->barProp = $b;
    }
}
EOD)->getClassDefinition('Foo');

        $this->assertSame('Bar', $type->parentFqn);
    }

    public static function extendableTemplateTypesProvider(): array
    {
        return DataProviders::extendableTemplateTypes();
    }
}

class Foo_ClassDefinitionTest
{
    public $prop = Builder::class;
}

class AfterFoo_ClassDefinitionTest implements AfterClassDefinitionCreatedExtension
{
    public function shouldHandle(string $name): bool
    {
        return $name === Foo_ClassDefinitionTest::class;
    }

    public function afterClassDefinitionCreated(ClassDefinitionCreatedEvent $event)
    {
        $event->classDefinition->templateTypes = [
            $t = new TemplateType('T'),
        ];
        $event->classDefinition->properties['prop'] = new ClassPropertyDefinition(
            type: new GenericClassStringType($t),
            defaultType: new GenericClassStringType(new ObjectType(Builder::class)),
        );
    }
}

class Bar_ClassDefinitionTest
{
    public function __construct(public $prop = Builder::class) {}
}

class AfterBar_ClassDefinitionTest implements AfterClassDefinitionCreatedExtension
{
    public function shouldHandle(string $name): bool
    {
        return $name === Bar_ClassDefinitionTest::class;
    }

    public function afterClassDefinitionCreated(ClassDefinitionCreatedEvent $event)
    {
        $event->classDefinition->templateTypes = [
            $t = new TemplateType('T'),
        ];
        $event->classDefinition->properties['prop'] = new ClassPropertyDefinition(
            type: new GenericClassStringType($t),
            defaultType: new GenericClassStringType(new ObjectType(Builder::class)),
        );
    }
}

class SetPropToMethod_ClassDefinitionTest
{
    public $prop;

    public function setProp($a)
    {
        $this->prop = $a;
    }
}
