<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands\Status;

use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Timestamp;
use DateTime;
use PHPUnit\Framework\TestCase;

class UpdateSubEventStatusTest extends TestCase
{
    /**
     * @var UpdateSubEventStatus
     */
    private $updateSubEventStatus;

    protected function setUp(): void
    {
        $this->updateSubEventStatus = new UpdateSubEventStatus(
            '7c9e3b3f-853a-4876-b7a0-82746fa295dc',
            Status::cancelled(),
            new Timestamp(
                new DateTime('2020-10-15T22:00:00+00:00'),
                new DateTime('2020-10-16T21:59:59+00:00')
            ),
            'Cancelled event'
        );
    }

    /**
     * @test
     */
    public function it_stores_an_event_id(): void
    {
        $this->assertEquals(
            '7c9e3b3f-853a-4876-b7a0-82746fa295dc',
            $this->updateSubEventStatus->getItemId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_status(): void
    {
        $this->assertEquals(
            Status::cancelled(),
            $this->updateSubEventStatus->getStatus()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_timestamp(): void
    {
        $this->assertEquals(
            new Timestamp(
                new DateTime('2020-10-15T22:00:00+00:00'),
                new DateTime('2020-10-16T21:59:59+00:00')
            ),
            $this->updateSubEventStatus->getTimestamp()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_reason(): void
    {
        $this->assertEquals(
            'Cancelled event',
            $this->updateSubEventStatus->getReason()
        );
    }
}
