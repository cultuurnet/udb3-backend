<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use JsonException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IriOfferIdentifierJSONDeserializerTest extends TestCase
{
    private IriOfferIdentifierJSONDeserializer $deserializer;

    private IriOfferIdentifierFactoryInterface&MockObject $iriOfferIdentifierFactory;

    public function setUp(): void
    {
        $this->iriOfferIdentifierFactory = $this->createMock(IriOfferIdentifierFactoryInterface::class);
        $this->deserializer = new IriOfferIdentifierJSONDeserializer(
            $this->iriOfferIdentifierFactory
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize_a_valid_iri_offer_identifier(): void
    {
        $json = '{"@id":"http://du.de/event/1","@type":"Event"}';

        $expected = new IriOfferIdentifier(
            new Url('http://du.de/event/1'),
            '1',
            OfferType::event()
        );

        $this->iriOfferIdentifierFactory->expects($this->once())
            ->method('fromIri')
            ->with(new Url('http://du.de/event/1'))
            ->willReturn($expected);

        $actual = $this->deserializer->deserialize($json);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_throws_a_json_exception_when_the_json_is_malformed(): void
    {
        $json = '{"foo"';

        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Syntax error');

        $this->deserializer->deserialize($json);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_id_is_missing(): void
    {
        $json = '{"@type":"Event"}';

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('Missing property "@id".');

        $this->deserializer->deserialize($json);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_type_is_missing(): void
    {
        $json = '{"@id":"http://du.de/event/1"}';

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('Missing property "@type".');

        $this->deserializer->deserialize($json);
    }
}
