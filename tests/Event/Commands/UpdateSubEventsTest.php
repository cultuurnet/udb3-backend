<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEventUpdate;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class UpdateSubEventsTest extends TestCase
{
    /**
     * @test
     */
    public function it_requires_at_least_one_event_update(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new UpdateSubEvents('event_id', ...[]);
    }

    /**
     * @test
     */
    public function it_stores_event_updates(): void
    {
        $subEventUpdates = [new SubEventUpdate(1), new SubEventUpdate(2)];
        $updateSubEvents = new UpdateSubEvents('event_id', ...$subEventUpdates);

        $this->assertEquals($subEventUpdates, $updateSubEvents->getUpdates());
    }

    /**
     * @test
     */
    public function it_implements_authorizable_command(): void
    {
        $updateSubEvents = new UpdateSubEvents('event_id', new SubEventUpdate(1));

        $this->assertEquals(Permission::aanbodBewerken(), $updateSubEvents->getPermission());
        $this->assertEquals('event_id', $updateSubEvents->getItemId());
    }
}
