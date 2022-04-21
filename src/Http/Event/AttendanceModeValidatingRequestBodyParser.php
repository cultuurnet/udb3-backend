<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Model\ValueObject\Virtual\AttendanceMode;
use Psr\Http\Message\ServerRequestInterface;

final class AttendanceModeValidatingRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        if (!is_object($data)) {
            return $request;
        }
        $hasVirtualLocation = (new LocationId($data->location->{'@id'}))->isVirtualLocation();
        $isOffline = !isset($data->attendanceMode) || $data->attendanceMode === AttendanceMode::offline()->toString();
        $isOnline = isset($data->attendanceMode) && $data->attendanceMode === AttendanceMode::online()->toString();

        if ($hasVirtualLocation && $isOffline) {
            throw ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/attendanceMode',
                    'Attendance mode "offline" can not be combined with a virtual location.'
                )
            );
        }

        if (!$hasVirtualLocation && $isOnline) {
            throw ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/attendanceMode',
                    'Attendance mode "online" needs to have a virtual location.'
                )
            );
        }

        return $request;
    }
}
