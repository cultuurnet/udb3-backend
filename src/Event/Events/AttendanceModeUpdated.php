<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use Broadway\Serializer\Serializable;

final class AttendanceModeUpdated implements Serializable
{
    private string $eventId;

    private string $attendanceMode;

    public function __construct(string $eventId, string $attendanceMode)
    {
        $this->eventId = $eventId;
        $this->attendanceMode = $attendanceMode;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getAttendanceMode(): string
    {
        return $this->attendanceMode;
    }

    public static function deserialize(array $data): AttendanceModeUpdated
    {
        return new AttendanceModeUpdated(
            $data['eventId'],
            $data['attendanceMode']
        );
    }

    public function serialize(): array
    {
        return [
            'eventId' => $this->eventId,
            'attendanceMode' => $this->attendanceMode,
        ];
    }
}
