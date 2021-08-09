<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemExceptionTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;

final class UpdateBookingAvailabilityRequestBodyParserTest extends TestCase
{
    use AssertApiProblemExceptionTrait;

    private UpdateBookingAvailabilityRequestBodyParser $updateBookingAvailabilityRequestBodyParser;

    private Psr7RequestBuilder $requestBuilder;

    protected function setUp(): void
    {
        $this->updateBookingAvailabilityRequestBodyParser = new UpdateBookingAvailabilityRequestBodyParser();
        $this->requestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_allows_valid_data(): void
    {
        $given = $this->requestBuilder->withBodyFromString('{"type":"Available"}')->build('PUT');
        $expected = [
            'type' => 'Available',
        ];

        $actual = $this->updateBookingAvailabilityRequestBodyParser->parse($given);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_fails_on_empty_body(): void
    {
        $given = $this->requestBuilder->withBodyFromString('')->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyMissing(),
            fn () => $this->updateBookingAvailabilityRequestBodyParser->parse($given)
        );
    }

    /**
     * @test
     */
    public function it_fails_on_unparsable_body(): void
    {
        $given = $this->requestBuilder->withBodyFromString('{{}')->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidSyntax('JSON'),
            fn () => $this->updateBookingAvailabilityRequestBodyParser->parse($given)
        );
    }

    /**
     * @test
     */
    public function it_fails_on_missing_type(): void
    {
        $given = $this->requestBuilder->withBodyFromString('{}')->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData('Required property "type" not found.', '/type'),
            fn () => $this->updateBookingAvailabilityRequestBodyParser->parse($given)
        );
    }

    /**
     * @test
     */
    public function it_fails_on_invalid_type(): void
    {
        $given = $this->requestBuilder->withBodyFromString('{"type":"foo"}')->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData('Invalid type provided.', '/type'),
            fn () => $this->updateBookingAvailabilityRequestBodyParser->parse($given)
        );
    }
}
