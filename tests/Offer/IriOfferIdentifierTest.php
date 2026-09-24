<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

class IriOfferIdentifierTest extends TestCase
{
    private IriOfferIdentifier $identifier;

    public function setUp(): void
    {
        $this->identifier = new IriOfferIdentifier(
            new Url('http://du.de/place/1'),
            '1',
            OfferType::place()
        );
    }

    /**
     * @test
     */
    public function it_can_be_serialized_and_unserialized(): void
    {
        $serialized = serialize($this->identifier);
        $unserialized = unserialize($serialized);

        $this->assertEquals($this->identifier, $unserialized);
    }

    /**
     * @test
     */
    public function it_can_still_be_unserialized_from_the_format_before_serialize_magic_methods(): void
    {
        $serialized = 'C:40:"CultuurNet\\UDB3\\Offer\\IriOfferIdentifier":58:{{"iri":"http:\\/\\/du.de\\/place\\/1","id":"1","type":"Place"}}';

        $this->assertEquals($this->identifier, unserialize($serialized));
    }

    /**
     * @test
     */
    public function it_returns_all_properties(): void
    {
        $this->assertEquals(new Url('http://du.de/place/1'), $this->identifier->getIri());
        $this->assertEquals('1', $this->identifier->getId());
        $this->assertEquals(OfferType::place(), $this->identifier->getType());
    }
}
