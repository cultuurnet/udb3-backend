<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Request\Body\RequestBodyInvalidData;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyInvalidSyntax;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyMissing;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

final class UpdateBookingAvailabilityRequestBodyParserTest extends TestCase
{
    /**
     * @var UpdateBookingAvailabilityRequestBodyParser
     */
    private $updateBookingAvailabilityRequestBodyParser;

    protected function setUp(): void
    {
        $this->updateBookingAvailabilityRequestBodyParser = new UpdateBookingAvailabilityRequestBodyParser();
    }

    private function createMockRequestWithBody(string $body): ServerRequestInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')
            ->willReturn($body);

        $mock = $this->createMock(ServerRequestInterface::class);
        $mock->method('getBody')
            ->willReturn($stream);

        /** @var ServerRequestInterface $mock */
        return $mock;
    }

    /**
     * @test
     */
    public function it_allows_valid_data(): void
    {
        $given = $this->createMockRequestWithBody('{"type":"Available"}');
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
        $given = $this->createMockRequestWithBody('');
        $this->expectException(RequestBodyMissing::class);
        $this->updateBookingAvailabilityRequestBodyParser->parse($given);
    }

    /**
     * @test
     */
    public function it_fails_on_unparsable_body(): void
    {
        $given = $this->createMockRequestWithBody('{{}');
        $this->expectException(RequestBodyInvalidSyntax::class);
        $this->updateBookingAvailabilityRequestBodyParser->parse($given);
    }

    /**
     * @test
     */
    public function it_fails_on_missing_type(): void
    {
        $given = $this->createMockRequestWithBody('{}');
        $this->expectException(RequestBodyInvalidData::class);
        $this->updateBookingAvailabilityRequestBodyParser->parse($given);
    }

    /**
     * @test
     */
    public function it_fails_on_invalid_type(): void
    {
        $given = $this->createMockRequestWithBody('{"type":"foo"}');
        $this->expectException(RequestBodyInvalidData::class);
        $this->updateBookingAvailabilityRequestBodyParser->parse($given);
    }
}
