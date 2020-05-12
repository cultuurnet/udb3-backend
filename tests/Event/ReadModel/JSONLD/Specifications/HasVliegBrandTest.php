<?php

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications;

use PHPUnit\Framework\TestCase;

class HasVliegBrandTest extends TestCase
{
    use EventSpecificationTestTrait;

    /**
     * @test
     */
    public function it_brands_events_aged_from_0_to_13_as_vlieg()
    {
        $event = $this->getEventLdFromFile('event_with_typical_age_range.json');

        // example age range should be set to "0-"
        $this->assertTrue((new HasVliegBrand())->isSatisfiedBy($event));

        $event->typicalAgeRange = '13-';
        $this->assertTrue((new HasVliegBrand())->isSatisfiedBy($event));

        $event->typicalAgeRange = '14-';
        $this->assertFalse((new HasVliegBrand())->isSatisfiedBy($event));
    }
}
