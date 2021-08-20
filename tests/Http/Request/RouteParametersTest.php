<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request;

use PHPUnit\Framework\TestCase;
use RuntimeException;

class RouteParametersTest extends TestCase
{
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
}
