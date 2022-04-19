<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Model\ValueObject\Virtual\AttendanceMode;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;

final class UpdateAttendanceMode implements AuthorizableCommand
{
    private string $eventId;

    private AttendanceMode $attendanceMode;

    public function __construct(string $eventId, AttendanceMode $attendanceMode)
    {
        $this->eventId = $eventId;
        $this->attendanceMode = $attendanceMode;
    }

    public function getItemId(): string
    {
        return $this->eventId;
    }

    public function getAttendanceMode(): AttendanceMode
    {
        return $this->attendanceMode;
    }

    public function getPermission(): Permission
    {
        return Permission::aanbodBewerken();
    }
}
