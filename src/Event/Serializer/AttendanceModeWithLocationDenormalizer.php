<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Serializer;

use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\Place\PlaceIDParser;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Online\AttendanceMode;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class AttendanceModeWithLocationDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = []): AttendanceModeWithLocation
    {
        $location = null;
        $attendanceMode = new AttendanceMode($data['attendanceMode']);

        if ($attendanceMode->sameAs(AttendanceMode::online())) {
            $location = new LocationId(Uuid::NIL);
        }

        if (!$attendanceMode->sameAs(AttendanceMode::online()) && isset($data['location'])) {
            $locationUrl = new Url($data['location']);
            $location = new LocationId((new PlaceIDParser())->fromUrl($locationUrl)->toString());
        }

        return new AttendanceModeWithLocation(
            $attendanceMode,
            $location
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === AttendanceModeWithLocation::class;
    }
}
