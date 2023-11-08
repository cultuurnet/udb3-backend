<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Auth;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class PublicRouteWithAuthMatcherTest extends TestCase
{
    private ServerRequestInterface $request;

    public function setUp(): void
    {
        $this->request = (new Psr7RequestBuilder())
            ->withRouteParameter('param', 'embedContributors')
            ->withUriFromString('/places?embedContributors')
            ->build('GET');
    }

    /**
     * @dataProvider configProvider
     */
    public function testIsAuthenticationRequired(array $config, bool $expectedResult): void
    {
        $this->assertEquals(
            $expectedResult,
            $this->constructPublicRouteWithAuthMatcher($config)->isAuthenticationRequired($this->request)
        );
    }

    public function configProvider(): array
    {
        return [
            [
                [
                    '~^/places/?$~' => [
                        'mode' => 'always',
                    ],
                ],
                true,
            ],
            [
                [
                    '~^/places/?$~' => [
                        'mode' => 'param',
                        'param' => 'embedContributors',
                    ],
                ],
                true,
            ],
            [
                [
                    '~^/places/?$~' => [
                        'mode' => 'param',
                        'param' => 'wrong',
                    ],
                ],
                false,
            ],
            [
                [
                    '~^/places/?$~' => [],
                ],
                false,
            ],
            [
                [],
                false,
            ],
        ];
    }

    private function constructPublicRouteWithAuthMatcher(array $config): PublicRouteWithAuthMatcher
    {
        $publicRouteWithAuthMatcher = new PublicRouteWithAuthMatcher($config);
        $publicRouteWithAuthMatcher->addPublicRoute(new PublicRouteRule('~^/places/?$~', ['GET']));

        return $publicRouteWithAuthMatcher;
    }
}
