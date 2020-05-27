<?php

namespace CultuurNet\UDB3\Offer;

use CultuurNet\Deserializer\MissingValueException;
use CultuurNet\Deserializer\NotWellFormedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class IriOfferIdentifierJSONDeserializerTest extends TestCase
{
    /**
     * @var IriOfferIdentifierJSONDeserializer
     */
    private $deserializer;

    /**
     * @var IriOfferIdentifierFactoryInterface|MockObject
     */
    private $iriOfferIdentifierFactory;

    public function setUp()
    {
        $this->iriOfferIdentifierFactory = $this->createMock(IriOfferIdentifierFactoryInterface::class);
        $this->deserializer = new IriOfferIdentifierJSONDeserializer(
            $this->iriOfferIdentifierFactory
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize_a_valid_iri_offer_identifier()
    {
        $json = new StringLiteral('{"@id":"http://du.de/event/1","@type":"Event"}');

        $expected = new IriOfferIdentifier(
            Url::fromNative("http://du.de/event/1"),
            "1",
            OfferType::EVENT()
        );

        $this->iriOfferIdentifierFactory->expects($this->once())
            ->method('fromIri')
            ->with('http://du.de/event/1')
            ->willReturn($expected);

        $actual = $this->deserializer->deserialize($json);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_the_json_is_malformed()
    {
        $json = new StringLiteral('{"foo"');

        $this->expectException(
            NotWellFormedException::class,
            'Invalid JSON'
        );

        $this->deserializer->deserialize($json);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_id_is_missing()
    {
        $json = new StringLiteral('{"@type":"Event"}');

        $this->expectException(
            MissingValueException::class,
            'Missing property "@id".'
        );

        $this->deserializer->deserialize($json);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_type_is_missing()
    {
        $json = new StringLiteral('{"@id":"http://du.de/event/1"}');

        $this->expectException(
            MissingValueException::class,
            'Missing property "@type".'
        );

        $this->deserializer->deserialize($json);
    }
}
