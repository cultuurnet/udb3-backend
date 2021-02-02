<?php

namespace CultuurNet\UDB3\Offer;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ValueObjects\Web\Url;

class OfferIdentifierCollectionTest extends TestCase
{
    /**
     * @test
     */
    public function it_only_accepts_offer_identifier_interface_instances()
    {
        $collection = new OfferIdentifierCollection();

        $collection = $collection->with(
            new IriOfferIdentifier(
                Url::fromNative('http://du.de/event/1'),
                '1',
                OfferType::EVENT()
            )
        );

        $this->assertEquals(1, $collection->length());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected instance of CultuurNet\UDB3\Offer\IriOfferIdentifier, found stdClass instead.');

        $collection->with(new \stdClass());
    }
}
