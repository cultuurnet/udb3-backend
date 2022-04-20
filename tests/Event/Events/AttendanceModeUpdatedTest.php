<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Model\ValueObject\Virtual\AttendanceMode;
use PHPUnit\Framework\TestCase;

final class AttendanceModeUpdatedTest extends TestCase
{
    private AttendanceModeUpdated $attendanceModeUpdated;

    protected function setUp(): void
    {
        $this->attendanceModeUpdated = new AttendanceModeUpdated(
            '21ade1c1-6bdb-4cc5-ad34-2028b60dcfbd',
            AttendanceMode::online()->toString()
        );
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $this->assertEquals(
            [
                'eventId' => '21ade1c1-6bdb-4cc5-ad34-2028b60dcfbd',
                'attendanceMode' => 'online',
            ],
            $this->attendanceModeUpdated->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
        $this->assertEquals(
            $this->attendanceModeUpdated,
            AttendanceModeUpdated::deserialize(
                [
                    'eventId' => '21ade1c1-6bdb-4cc5-ad34-2028b60dcfbd',
                    'attendanceMode' => 'online',
                ]
            )
        );
    }
}
