<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;

class JsonRequestBodyParserTest extends TestCase
{
    use AssertApiProblemTrait;

    private JsonRequestBodyParser $parser;

    private Psr7RequestBuilder $requestBuilder;

    protected function setUp(): void
    {
        $this->parser = new JsonRequestBodyParser();
        $this->requestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_returns_the_decoded_json_body_as_an_associative_array(): void
    {
        $given = $this->requestBuilder->withBodyFromString('{"foo":"bar"}')->build('PUT');
        $expected = (object) ['foo' => 'bar'];
        $actual = $this->parser->parse($given);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_throws_when_body_is_missing(): void
    {
        $given = $this->requestBuilder->withBodyFromString('')->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyMissing(),
            fn () => $this->parser->parse($given)
        );
    }

    /**
     * @test
     */
    public function it_throws_when_body_is_invalid_json(): void
    {
        $given = $this->requestBuilder->withBodyFromString('{{}')->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidSyntax('JSON'),
            fn () => $this->parser->parse($given)
        );
    }
}
