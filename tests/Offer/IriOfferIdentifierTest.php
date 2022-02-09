<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

class IriOfferIdentifierTest extends TestCase
{
    /**
     * @var IriOfferIdentifier
     */
    private $identifier;

    public function setUp()
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
        $this->assertEquals(new Url('http://du.de/place/1'), $this->identifier->getIri());
        $this->assertEquals('1', $this->identifier->getId());
        $this->assertEquals(OfferType::place(), $this->identifier->getType());
    }
}
