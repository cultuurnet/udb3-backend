<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblemException;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;

final class UpdateBookingAvailabilityRequestBodyParserTest extends TestCase
{
    /**
     * @var UpdateBookingAvailabilityRequestBodyParser
     */
    private $updateBookingAvailabilityRequestBodyParser;

    /**
     * @var Psr7RequestBuilder
     */
    private $requestBuilder;

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
        $this->expectException(ApiProblemException::class);
        $this->updateBookingAvailabilityRequestBodyParser->parse($given);
    }

    /**
     * @test
     */
    public function it_fails_on_unparsable_body(): void
    {
        $given = $this->requestBuilder->withBodyFromString('{{}')->build('PUT');
        $this->expectException(ApiProblemException::class);
        $this->updateBookingAvailabilityRequestBodyParser->parse($given);
    }

    /**
     * @test
     */
    public function it_fails_on_missing_type(): void
    {
        $given = $this->requestBuilder->withBodyFromString('{}')->build('PUT');
        $this->expectException(ApiProblemException::class);
        $this->updateBookingAvailabilityRequestBodyParser->parse($given);
    }

    /**
     * @test
     */
    public function it_fails_on_invalid_type(): void
    {
        $given = $this->requestBuilder->withBodyFromString('{"type":"foo"}')->build('PUT');
        $this->expectException(ApiProblemException::class);
        $this->updateBookingAvailabilityRequestBodyParser->parse($given);
    }
}
