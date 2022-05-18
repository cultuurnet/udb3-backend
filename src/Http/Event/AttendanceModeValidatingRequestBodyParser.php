<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Model\ValueObject\Online\AttendanceMode;
use Psr\Http\Message\ServerRequestInterface;

final class AttendanceModeValidatingRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        if (!is_object($data)) {
            return $request;
        }
        $isOnlineLocation = (new LocationId($data->location->{'@id'}))->isOnlineLocation();
        $isOffline = !isset($data->attendanceMode) || $data->attendanceMode === AttendanceMode::offline()->toString();
        $isOnline = isset($data->attendanceMode) && $data->attendanceMode === AttendanceMode::online()->toString();
        $isMixed = isset($data->attendanceMode) && $data->attendanceMode === AttendanceMode::mixed()->toString();

        if ($isOnlineLocation && $isOffline) {
            throw ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/attendanceMode',
                    'Attendance mode "offline" needs to have a real location.'
                )
            );
        }

        if (!$isOnlineLocation && $isOnline) {
            throw ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/attendanceMode',
                    'Attendance mode "online" needs to have an online location.'
                )
            );
        }

        if ($isOnlineLocation && $isMixed) {
            throw ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/attendanceMode',
                    'Attendance mode "mixed" needs to have a real location.'
                )
            );
        }

        return $request;
    }
}
