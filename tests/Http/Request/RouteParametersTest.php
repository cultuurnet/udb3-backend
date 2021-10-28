<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RouteParametersTest extends TestCase
{
    use AssertApiProblemTrait;

    /**
     * @test
     */
    public function it_should_return_an_existing_route_parameter_from_the_request_as_string(): void
    {
        $request = (new Psr7RequestBuilder())->build('PUT');
        $request = $request->withAttribute('_route_params', ['foo' => 'bar']);
        $routeParameters = new RouteParameters($request);

        $this->assertEquals('bar', $routeParameters->get('foo'));
    }

    /**
     * @test
     */
    public function it_should_throw_a_runtime_exception_if_a_parameter_is_requested_that_is_not_set(): void
    {
        $request = (new Psr7RequestBuilder())->build('PUT');
        $request = $request->withAttribute('_route_params', []);
        $routeParameters = new RouteParameters($request);

        $this->expectException(RuntimeException::class);
        $routeParameters->get('foo');
    }

    /**
     * @test
     */
    public function it_should_throw_a_runtime_exception_if_a_parameter_is_requested_and_none_are_set(): void
    {
        $request = (new Psr7RequestBuilder())->build('PUT');
        $routeParameters = new RouteParameters($request);

        $this->expectException(RuntimeException::class);
        $routeParameters->get('foo');
    }

    /**
     * @test
     */
    public function it_returns_a_valid_language(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('language', 'nl')
            ->build('PUT');
        $routeParameters = new RouteParameters($request);

        $this->assertEquals(new Language('nl'), $routeParameters->getLanguage());
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_language(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('language', 'BE-nl')
            ->build('PUT');
        $routeParameters = new RouteParameters($request);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::pathParameterInvalid(
                'The provided language route parameter is not supported.'
            ),
            fn () => $routeParameters->getLanguage()
        );
    }
}
