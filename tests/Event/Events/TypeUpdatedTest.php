<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use PHPUnit\Framework\TestCase;

class TypeUpdatedTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_be_serializable(): void
    {
        $event = new TypeUpdated(
            '89491DC9-9C33-4145-ABB4-AEB33FD93CB6',
            new Category(new CategoryID('0.17.0.0.0'), new CategoryLabel('Route'), CategoryDomain::eventType())
        );

        $eventData = $event->serialize();
        $deserializedEvent = TypeUpdated::deserialize($eventData);

        $this->assertEquals($event, $deserializedEvent);
    }
}
