<?php

namespace CultuurNet\UDB3\Cdb\Event;

use PHPUnit\Framework\TestCase;

class AnyTest extends TestCase
{
    /**
     * @test
     */
    public function it_is_satisfied_by_any_event()
    {
        $spec = new Any();

        $event = new \CultureFeed_Cdb_Item_Event();

        $this->assertTrue($spec->isSatisfiedByEvent($event));
    }
}
