<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request;

use PHPUnit\Framework\TestCase;

class QueryParametersTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_parameter_value_as_a_string(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/example?foo=bar')
            ->build('GET');

        $queryParameters = new QueryParameters($request);
        $this->assertSame('bar', $queryParameters->get('foo'));
    }

    /**
     * @test
     */
    public function it_returns_the_default_value_if_the_parameter_is_not_set(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/example')
            ->build('GET');

        $queryParameters = new QueryParameters($request);
        $this->assertSame('ipsum', $queryParameters->get('foo', 'ipsum'));
    }

    /**
     * @test
     */
    public function it_returns_null_if_the_parameter_is_not_set_and_no_default_is_given(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/example')
            ->build('GET');

        $queryParameters = new QueryParameters($request);
        $this->assertNull($queryParameters->get('foo'));
    }

    /**
     * @test
     * @dataProvider booleanDataProvider
     */
    public function it_parses_parameters_as_booleans(string $valueAsString, ?bool $expectedResult = null): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/example?foo=' . $valueAsString)
            ->build('GET');

        $queryParameters = new QueryParameters($request);
        $this->assertSame($expectedResult, $queryParameters->getAsBoolean('foo'));
    }

    public function booleanDataProvider(): array
    {
        return [
            [
                'valueAsString' => '1',
                'expectedResult' => true,
            ],
            [
                'valueAsString' => 'true',
                'expectedResult' => true,
            ],
            [
                'valueAsString' => 'anystring',
                'expectedResult' => false,
            ],
            [
                'valueAsString' => 'false',
                'expectedResult' => false,
            ],
            [
                'valueAsString' => '0',
                'expectedResult' => false,
            ],
            [
                'valueAsString' => '',
                'expectedResult' => false,
            ],
        ];
    }
}
