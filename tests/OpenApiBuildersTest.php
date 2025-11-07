<?php

namespace Dedoc\Scramble\Tests;

use Dedoc\Scramble\Support\Generator\InfoObject;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\SecuritySchemes\OAuthFlow;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Snapshots\MatchesSnapshots;

final class OpenApiBuildersTest extends SymfonyTestCase
{
    use MatchesSnapshots;

    #[Test]
    public function buildsSecurityScheme(): void
    {
        $openApi = (new OpenApi('3.1.0'))
            ->setInfo(InfoObject::make('API')->setVersion('0.0.1'));

        $openApi->secure(SecurityScheme::apiKey('query', 'api_token'));
        $document = $openApi->toArray();

        $this->assertSame([['apiKey' => []]], $document['security']);
        $this->assertSame([
            'apiKey' => [
                'type' => 'apiKey',
                'in' => 'query',
                'name' => 'api_token',
            ],
        ], $document['components']['securitySchemes']);
    }

    #[Test]
    public function buildsOauth2SecurityScheme(): void
    {
        $openApi = (new OpenApi('3.1.0'))
            ->setInfo(InfoObject::make('API')->setVersion('0.0.1'));

        $openApi->secure(
            SecurityScheme::oauth2()
                ->flow('implicit', function (OAuthFlow $flow) {
                    $flow
                        ->refreshUrl('https://test.com')
                        ->tokenUrl('https://test.com/token')
                        ->addScope('wow', 'nice');
                })
        );

        $this->assertMatchesSnapshot($openApi->toArray());
    }

    #[Test]
    public function buildsOauth2SecuritySchemeWithEmptyScopeMap(): void
    {
        $openApi = (new OpenApi('3.1.0'))
            ->setInfo(InfoObject::make('API')->setVersion('0.0.1'));

        $openApi->secure(
            SecurityScheme::oauth2()
                ->flow('implicit', function (OAuthFlow $flow) {
                    $flow
                        ->refreshUrl('https://test.com')
                        ->tokenUrl('https://test.com/token');
                })
        );
        $document = $openApi->toArray();

        $this->assertIsObject($document['components']['securitySchemes']['oauth2']['flows']['implicit']['scopes']);
    }

    #[Test]
    public function allowsSecuringWithComplexSecurityRules(): void
    {
        $openApi = (new OpenApi('3.1.0'))
            ->setInfo(InfoObject::make('API')->setVersion('0.0.1'));

        $openApi->components->securitySchemes['tenant'] = SecurityScheme::apiKey('header', 'X-Tenant');
        $openApi->components->securitySchemes['bearer'] = SecurityScheme::http('bearer');

        $openApi->security[] = new SecurityRequirement([
            'tenant' => [],
            'bearer' => [],
        ]);

        $serialized = $openApi->toArray();

        $this->assertSame([[
            'tenant' => [],
            'bearer' => [],
        ]], $serialized['security']);
        $this->assertSame([
            'tenant' => [
                'type' => 'apiKey',
                'in' => 'header',
                'name' => 'X-Tenant',
            ],
            'bearer' => [
                'type' => 'http',
                'scheme' => 'bearer',
            ],
        ], $serialized['components']['securitySchemes']);
    }
}
