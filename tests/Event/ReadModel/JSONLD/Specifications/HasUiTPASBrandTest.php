<?php

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications;

use PHPUnit\Framework\TestCase;

class HasUiTPASBrandTest extends TestCase
{
    use EventSpecificationTestTrait;

    /**
     * @test
     */
    public function it_brands_events_with_label_UiTPAS_as_UiTPAS()
    {
        $event = $this->getEventLdFromFile('event_with_all_icon_labels.json');
        $this->assertFalse((new HasUiTPASBrand())->isSatisfiedBy($event));

        $event->labels = ['UiTPAS Regio Aalst'];
        $this->assertTrue((new HasUiTPASBrand())->isSatisfiedBy($event));

        $event->labels = ['UiTPAS Gent'];
        $this->assertTrue((new HasUiTPASBrand())->isSatisfiedBy($event));

        $event->labels = ['Paspartoe'];
        $this->assertTrue((new HasUiTPASBrand())->isSatisfiedBy($event));
    }
}
