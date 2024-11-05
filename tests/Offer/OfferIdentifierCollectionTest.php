<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;
use TypeError;

class OfferIdentifierCollectionTest extends TestCase
{
    /**
     * @test
     */
    public function it_only_accepts_offer_identifier_interface_instances(): void
    {
        $collection = new OfferIdentifierCollection();

        $collection = $collection->with(
            new IriOfferIdentifier(
                new Url('http://du.de/event/1'),
                '1',
                OfferType::event()
            )
        );

        $this->assertEquals(1, $collection->count());

        $this->expectException(TypeError::class);

        $collection->with(new \stdClass());
    }
}
