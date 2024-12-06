<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use PHPUnit\Framework\TestCase;

class ThemeUpdatedTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_be_serializable(): void
    {
        $event = new ThemeUpdated(
            '9B70683A-5ABF-4A21-80CE-D3A1C0C7BCC2',
            new Category(new CategoryID('0.52.0.0.0'), new CategoryLabel('Circus'), CategoryDomain::theme())
        );

        $eventData = $event->serialize();
        $deserializedEvent = ThemeUpdated::deserialize($eventData);

        $this->assertEquals($event, $deserializedEvent);
    }
}
