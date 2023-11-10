<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Auth;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class PublicRouteRuleTest extends TestCase
{
    private ServerRequestInterface $request;

    public function setUp(): void
    {
        $this->request = (new Psr7RequestBuilder())
            ->withUriFromString('/places')
            ->build('GET');
    }
    
    public function testMatchesRequestWithMatchingRoute(): void
    {
        $routeRule = new PublicRouteRule('/\/places/', ['GET']);

        $this->assertTrue($routeRule->matchesRequest($this->request));
    }

    public function testMatchesRequestWithNonMatchingRoute(): void
    {
        $routeRule = new PublicRouteRule('/\/invalid/', ['GET']);

        $this->assertFalse($routeRule->matchesRequest($this->request));
    }

    public function testMatchesRequestWithExcludedQueryParamOnTrue(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/places?embedContributors=true')
            ->build('GET');

        // Create a RouteRule instance with an excluded query parameter
        $routeRule = new PublicRouteRule('/\/places/', ['GET'], 'embedContributors');

        // Test if the route matches the request considering the excluded query parameter
        $this->assertFalse($routeRule->matchesRequest($request));
    }

    public function testMatchesRequestWithExcludedQueryParamOnFalse(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/places?embedContributors=false')
            ->build('GET');

        // Create a RouteRule instance with an excluded query parameter
        $routeRule = new PublicRouteRule('/\/places/', ['GET'], 'embedContributors');

        // Test if the route matches the request considering the excluded query parameter
        $this->assertTrue($routeRule->matchesRequest($request));
    }
}
