<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\JsonRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class QueryValidatingRequestBodyParserTest extends TestCase
{
    use AssertApiProblemTrait;

    private QueryValidatingRequestBodyParser $parser;

    private JsonRequestBodyParser $jsonParser;

    protected function setUp()
    {
        $this->parser = new QueryValidatingRequestBodyParser();
        $this->jsonParser = new JsonRequestBodyParser();
    }

    /**
     * @test
     */
    public function it_throws_when_the_query_parameter_is_not_provided(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray([])
            ->build('POST');

        $request = $this->givenTheRequestWasJsonParsed($request);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/', 'The required properties (query) are missing'),
            ),
            fn () => $this->parser->parse($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_when_the_query_parameter_is_empty(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray(['query' => ''])
            ->build('POST');

        $request = $this->givenTheRequestWasJsonParsed($request);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/', 'The required properties (query) are missing'),
            ),
            fn () => $this->parser->parse($request)
        );
    }

    /**
     * @test
     */
    public function it_returns_the_request(): void
    {
        $query = 'Can I ask something';
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray(['query' => $query])
            ->build('POST');

        $request = $this->givenTheRequestWasJsonParsed($request);

        $request = $this->parser->parse($request);

        /** @var \stdClass $data */
        $data = $request->getParsedBody();
        $this->assertEquals($query, $data->query);
    }

    private function givenTheRequestWasJsonParsed(ServerRequestInterface $request): ServerRequestInterface
    {
        return $this->jsonParser->parse($request);
    }
}
