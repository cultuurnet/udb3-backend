<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Serializer;

use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Online\AttendanceMode;

final class AttendanceModeWithLocation
{
    private AttendanceMode $attendanceMode;

    private ?LocationId $locationId;

    public function __construct(AttendanceMode $attendanceMode, ?LocationId $locationId)
    {
        $this->attendanceMode = $attendanceMode;
        $this->locationId = $locationId;
    }

    public function getAttendanceMode(): AttendanceMode
    {
        return $this->attendanceMode;
    }

    public function getLocationId(): ?LocationId
    {
        return $this->locationId;
    }
}
