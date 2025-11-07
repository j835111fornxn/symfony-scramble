<?php

namespace Dedoc\Scramble\Tests\Infer;

/*
 * Reference types are the types which are created when there is no available info at the moment
 * of nodes traversal. Later, after the fn or class is traversed, references are resolved.
 */

use Dedoc\Scramble\Tests\Support\AnalysisHelpers;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ReferenceTypesTest extends SymfonyTestCase
{
    use AnalysisHelpers;

    /*
     * References in own class.
     */
    #[Test]
    public function resolvesAReferenceWhenEncounteredInSelfClass(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public function foo () {
        return $this->bar();
    }
    public function bar () {
        return 2;
    }
}
EOD)->getClassDefinition('Foo');

        $this->assertSame('(): int(2)', $type->methods['bar']->type->toString());
        $this->assertSame('(): int(2)', $type->methods['foo']->type->toString());
    }

    #[Test]
    public function correctlyReplacesTemplatesWithoutModifyingType(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public function foo ($a) {
        return ['a' => $a];
    }
}
EOD);

        /*
         * Previously this test would fail due to original return type being mutated.
         */
        $type->getExpressionType('(new Foo)->foo(123)');

        $this->assertSame('array{a: int(42)}', $type->getExpressionType('(new Foo)->foo(42)')->toString());
    }

    #[Test]
    public function resolvesACyclicReferenceSafely(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public function foo () {
        if (piu()) {
            return 1;
        }
        return $this->foo();
    }
}
EOD)->getClassDefinition('Foo');

        $this->assertSame('(): unknown|int(1)', $type->methods['foo']->type->toString());
    }

    #[Test]
    public function resolvesAnIndirectReference(): void
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
    public function resolvesACyclicReferenceIntroducedByTemplateMethodCall(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public function foo($q)
    {
        return $q->wow();
    }
}
EOD)->getClassDefinition('Foo');

        $this->assertSame('<TQ>(TQ): unknown', $type->methods['foo']->type->toString());
    }

    #[Test]
    public function resolvesACyclicReferenceIntroducedByTemplatePropertyFetch(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public function foo ()
    {
        return fn($q) => $q->prop;
    }
}
EOD)->getClassDefinition('Foo');

        $this->assertSame('(): <TQ>(TQ): unknown', $type->methods['foo']->type->toString());
    }

    #[Test]
    public function resolvesReferencesInNonReferenceReturnTypes(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public function foo () {
        return [$this->two(), $this->two()];
    }
    public function two () {
        return 2;
    }
}
EOD)->getClassDefinition('Foo');

        $this->assertSame('(): list{int(2), int(2)}', $type->methods['foo']->type->toString());
    }

    #[Test]
    public function resolvesUnknownReferencesToUnknownsInNonReferenceReturnTypes(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public function foo () {
        return [$this->two(), $this->three()];
    }
    public function two () {
        return 2;
    }
}
EOD)->getClassDefinition('Foo');

        $this->assertSame('(): list{int(2), unknown}', $type->methods['foo']->type->toString());
    }

    #[Test]
    public function resolvesADeepReferenceWhenEncounteredInSelfClass(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo {
    public function foo () {
        return $this->bar()->bar()->two();
    }
    public function bar () {
        return $this;
    }
    public function two () {
        return 2;
    }
}
EOD)->getClassDefinition('Foo');

        $this->assertSame('(): int(2)', $type->methods['foo']->type->toString());
    }

    #[Test]
    public function resolvesAReferenceFromFunction(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
function foo () {
    return bar();
}
function bar () {
    return 2;
}
EOD)->getFunctionDefinition('foo');

        $this->assertSame('(): int(2)', $type->type->toString());
    }

    #[Test]
    public function resolvesReferencesInUnknownsAfterTraversal(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class PendingUnknownWithSelfReference
{
    public function returnSomeCall()
    {
        return some();
    }

    public function returnThis()
    {
        return $this;
    }
}
EOD)->getClassDefinition('PendingUnknownWithSelfReference');

        $this->assertSame('(): unknown', $type->methods['returnSomeCall']->type->toString());
        $this->assertSame('(): self', $type->methods['returnThis']->type->toString());
    }

    #[Test]
    public function resolvesDeepReferencesInUnknownsAfterTraversal(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo
{
    public function returnSomeCall()
    {
        if ($a) {
            return foobarfoo($erw);
        }
        return (new Bar)->some()->call();
    }
}
EOD)->getClassDefinition('Foo');

        $this->assertSame('(): unknown', $type->methods['returnSomeCall']->type->toString());
    }

    #[Test]
    public function handlesUsageOfTypeAnnotationWhenResolvedInferredTypeIsUnknown(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
class Foo
{
    public function returnSomeCall()
    {
        return $this->bar();
    }

    public function bar(): SomeClass {
        return foo();
    }
}
EOD)->getClassDefinition('Foo');

        $this->assertSame('(): SomeClass', $type->methods['returnSomeCall']->type->toString());
    }
}
