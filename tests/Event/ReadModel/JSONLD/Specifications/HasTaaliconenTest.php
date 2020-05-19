<?php

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications;

use PHPUnit\Framework\TestCase;

class HasTaaliconenTest extends TestCase
{

    use EventSpecificationTestTrait;

    /**
     * @test
     */
    public function it_checks_if_an_event_has_taaliconen()
    {
        $event = $this->getEventLdFromFile('event_with_all_icon_labels.json');
        $this->assertTrue((new Has1Taalicoon())->isSatisfiedBy($event));
        $this->assertTrue((new Has2Taaliconen())->isSatisfiedBy($event));
        $this->assertTrue((new Has3Taaliconen())->isSatisfiedBy($event));
        $this->assertTrue((new Has4Taaliconen())->isSatisfiedBy($event));


        $event->labels = array('some_random_label');
        $this->assertFalse((new Has1Taalicoon())->isSatisfiedBy($event));
        $this->assertFalse((new Has2Taaliconen())->isSatisfiedBy($event));
        $this->assertFalse((new Has3Taaliconen())->isSatisfiedBy($event));
        $this->assertFalse((new Has4Taaliconen())->isSatisfiedBy($event));
    }
}
