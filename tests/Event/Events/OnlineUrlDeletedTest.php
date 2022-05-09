<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use PHPUnit\Framework\TestCase;

final class OnlineUrlDeletedTest extends TestCase
{
    private OnlineUrlDeleted $onlineUrlDeleted;

    protected function setUp(): void
    {
        $this->onlineUrlDeleted = new OnlineUrlDeleted('8ca71433-7a38-4c46-bc01-b3388da89214');
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
        $this->assertEquals(
            $this->onlineUrlDeleted,
            OnlineUrlDeleted::deserialize([
                'eventId' => '8ca71433-7a38-4c46-bc01-b3388da89214',
            ])
        );
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $this->assertEquals(
            [
                'eventId' => '8ca71433-7a38-4c46-bc01-b3388da89214',
            ],
            $this->onlineUrlDeleted->serialize()
        );
    }
}
