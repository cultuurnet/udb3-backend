<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\Label;
use PHPUnit\Framework\TestCase;

class LabelRemovedTest extends TestCase
{
    /**
     * @test
     */
    public function it_derives_from_abstract_label_event()
    {
        $labelRemoved = new LabelRemoved('organizerId', 'foo');

        $this->assertInstanceOf(AbstractLabelEvent::class, $labelRemoved);
    }
}
