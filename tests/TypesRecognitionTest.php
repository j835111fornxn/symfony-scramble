<?php

namespace Dedoc\Scramble\Tests;

use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\PhpDoc\PhpDocTypeHelper;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\PhpDoc;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

// @todo move all tests into PhpDoc/PhpDocTypeHelperTest

final class TypesRecognitionTest extends TestCase
{
    use MatchesSnapshots;

    protected function getTypeFromDoc(string $phpDoc)
    {
        $docNode = PhpDoc::parse($phpDoc);
        $varNode = $docNode->getVarTagValues()[0];

        // TODO: Replace app() with proper Symfony container access
        return app()->make(TypeTransformer::class, [
            'context' => new OpenApiContext(new OpenApi('3.1.0'), new GeneratorConfig),
        ])->transform(PhpDocTypeHelper::toType($varNode->type));
    }

    protected function getPhpTypeFromDoc(string $phpDoc)
    {
        $docNode = PhpDoc::parse($phpDoc);
        $varNode = $docNode->getVarTagValues()[0];

        return PhpDocTypeHelper::toType($varNode->type);
    }

    #[Test]
    #[DataProvider('simpleTypesProvider')]
    public function handlesSimpleTypes(string $phpDoc): void
    {
        $result = $this->getTypeFromDoc($phpDoc);

        $this->assertMatchesSnapshot($result ? $result->toArray() : null);
    }

    public static function simpleTypesProvider(): array
    {
        return [
            ['/** @var string */'],
            ['/** @var int */'],
            ['/** @var integer */'],
            ['/** @var float */'],
            ['/** @var bool */'],
            ['/** @var boolean */'],
            ['/** @var true */'],
            ['/** @var false */'],
            ['/** @var float */'],
            ['/** @var double */'],
            ['/** @var scalar */'],
            ['/** @var array */'],
            ['/** @var null */'],
            ['/** @var object */'],
        ];
    }

    #[Test]
    #[DataProvider('literalTypesProvider')]
    public function handlesLiteralTypes(string $phpDoc, string $expectedType): void
    {
        $result = $this->getPhpTypeFromDoc($phpDoc);

        $this->assertSame($expectedType, $result->toString());
    }

    public static function literalTypesProvider(): array
    {
        return [
            ["/** @var 'foo' */", 'string(foo)'],
            ['/** @var true */', 'boolean(true)'],
            ['/** @var false */', 'boolean(false)'],
            ["/** @var array{'foo': string} */", 'array{foo: string}'],
        ];
    }

    /**
     * @see https://phpstan.org/writing-php-code/phpdoc-types#general-arrays
     */
    #[Test]
    #[DataProvider('generalArraysProvider')]
    public function handlesGeneralArrays(string $phpDoc): void
    {
        $result = $this->getTypeFromDoc($phpDoc);

        $this->assertMatchesSnapshot($result ? $result->toArray() : null);
    }

    public static function generalArraysProvider(): array
    {
        return [
            ['/** @var string[] */'],
            ['/** @var array<string> */'],
            ['/** @var array<int, string> */'],
            ['/** @var array<string, string> */'],
        ];
    }

    #[Test]
    #[DataProvider('shapeArraysProvider')]
    public function handlesShapeArrays(string $phpDoc): void
    {
        $result = $this->getTypeFromDoc($phpDoc);

        $this->assertMatchesSnapshot($result ? $result->toArray() : null);
    }

    public static function shapeArraysProvider(): array
    {
        return [
            ['/** @var array{string} */'], // list with one item
            ['/** @var array{int, string} */'], // list
            ['/** @var array{0: string, 1: string} */'], // list
            ['/** @var array{wow: string} */'], // keyed
            ['/** @var array{test: string, wow?: string} */'], // keyed, test var here is added so snapshot name generates correctly
            ['/** @var array{string, string} */'], // list
        ];
    }

    #[Test]
    public function handlesIntersectionType(): void
    {
        $phpDoc = '/** @var array{test: string, wow?: string} & array{nice: bool} & array{kek: bool} */';
        $result = $this->getTypeFromDoc($phpDoc);

        $this->assertMatchesSnapshot($result ? $result->toArray() : null);
    }

    #[Test]
    public function handlesUnionType(): void
    {
        $phpDoc = '/** @var array{test: string, wow?: string} | array{nice: bool} | array{kek: bool} */';
        $result = $this->getTypeFromDoc($phpDoc);

        $this->assertMatchesSnapshot($result ? $result->toArray() : null);
    }

    #[Test]
    #[DataProvider('unionsOfStringLiteralsProvider')]
    public function handlesUnionsOfStringLiterals(string $phpDoc): void
    {
        $result = $this->getTypeFromDoc($phpDoc);

        $this->assertMatchesSnapshot($result ? $result->toArray() : null);
    }

    public static function unionsOfStringLiteralsProvider(): array
    {
        return [
            ["/** @var 'foo'|'bar' */"],
            ["/** @var 'foo'|'bar'|string */"],
        ];
    }
}
