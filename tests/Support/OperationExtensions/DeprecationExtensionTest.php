<?php

namespace Dedoc\Scramble\Tests\Support\OperationExtensions;

use Dedoc\Scramble\Tests\SymfonyTestCase;

final class DeprecationExtensionTest extends SymfonyTestCase
{
    public function testDeprecatedMethodSetsDeprecationKey(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [Deprecated_ResponseExtensionTest_Controller::class, 'deprecated']);
        });

        $this->assertArrayNotHasKey('description', $openApiDocument['paths']['/test']['get']);
        $this->assertArrayHasKey('deprecated', $openApiDocument['paths']['/test']['get']);
        $this->assertTrue($openApiDocument['paths']['/test']['get']['deprecated']);
    }

    public function testDeprecatedMethodSetsKeyAndDescription(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [Deprecated_Description_ResponseExtensionTest_Controller::class, 'deprecated']);
        });

        $this->assertSame('Deprecation description', $openApiDocument['paths']['/test']['get']['description']);
        $this->assertTrue($openApiDocument['paths']['/test']['get']['deprecated']);
    }

    public function testDeprecatedClassWithDescriptionSetsKeys(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [Deprecated_Class_Description_ResponseExtensionTest_Controller::class, 'deprecated']);
        });

        $this->assertSame('Class description'."\n\n".'Deprecation description', $openApiDocument['paths']['/test']['get']['description']);
        $this->assertTrue($openApiDocument['paths']['/test']['get']['deprecated']);
    }

    public function testDeprecatedClassWithoutDescriptionSetsKeys(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [Deprecated_Class_ResponseExtensionTest_Controller::class, 'deprecated']);
        });

        $this->assertArrayNotHasKey('description', $openApiDocument['paths']['/test']['get']);
        $this->assertTrue($openApiDocument['paths']['/test']['get']['deprecated']);
    }

    public function testNotDeprecatedIgnoresTheClassDeprecation(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [Not_Deprecated_Class_ResponseExtensionTest_Controller::class, 'notDeprecated']);
        });

        $this->assertArrayNotHasKey('description', $openApiDocument['paths']['/test']['get']);
        $this->assertArrayNotHasKey('deprecated', $openApiDocument['paths']['/test']['get']);
    }
}

class Deprecated_ResponseExtensionTest_Controller
{
    /**
     * @deprecated
     */
    public function deprecated()
    {
        return false;
    }
}

class Deprecated_Description_ResponseExtensionTest_Controller
{
    /**
     * @deprecated Deprecation description
     *
     * @response array{ "test": "test"}
     */
    public function deprecated()
    {
        return false;
    }
}

/** @deprecated Class description */
class Deprecated_Class_Description_ResponseExtensionTest_Controller
{
    /**
     * @deprecated Deprecation description
     *
     * @response array{ "test": "test"}
     */
    public function deprecated()
    {
        return false;
    }
}

/** @deprecated */
class Deprecated_Class_ResponseExtensionTest_Controller
{
    /**
     * @response array{ "test": "test"}
     */
    public function deprecated()
    {
        return false;
    }
}

/** @deprecated */
class Not_Deprecated_Class_ResponseExtensionTest_Controller
{
    /**
     * @not-deprecated
     */
    public function notDeprecated()
    {
        return false;
    }
}
