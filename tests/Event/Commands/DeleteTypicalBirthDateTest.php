<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use PHPUnit\Framework\TestCase;

class DeleteTypicalBirthDateTest extends TestCase
{
    /**
     * @test
     */
    public function it_stores_an_item_id(): void
    {
        $command = new DeleteTypicalBirthDate('event-id');

        $this->assertEquals('event-id', $command->getItemId());
    }
}
