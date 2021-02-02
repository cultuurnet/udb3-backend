<?php

namespace CultuurNet\UDB3\Offer;

use PHPUnit\Framework\TestCase;
use ValueObjects\Web\Url;

class IriOfferIdentifierTest extends TestCase
{
    /**
     * @var IriOfferIdentifier
     */
    private $identifier;

    public function setUp()
    {
        $this->identifier = new IriOfferIdentifier(
            Url::fromNative('http://du.de/place/1'),
            '1',
            OfferType::PLACE()
        );
    }

    /**
     * @test
     */
    public function it_can_be_serialized_and_unserialized()
    {
        $serialized = serialize($this->identifier);
        $unserialized = unserialize($serialized);

        $this->assertEquals($this->identifier, $unserialized);
    }

    /**
     * @test
     */
    public function it_returns_all_properties()
    {
        $this->assertEquals(Url::fromNative('http://du.de/place/1'), $this->identifier->getIri());
        $this->assertEquals('1', $this->identifier->getId());
        $this->assertEquals(OfferType::PLACE(), $this->identifier->getType());
    }
}
