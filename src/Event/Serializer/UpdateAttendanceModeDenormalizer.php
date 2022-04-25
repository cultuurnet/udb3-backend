<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Serializer;

use CultuurNet\UDB3\Event\Commands\UpdateAttendanceMode;
use CultuurNet\UDB3\Model\ValueObject\Virtual\AttendanceMode;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UpdateAttendanceModeDenormalizer implements DenormalizerInterface
{
    private string $eventId;

    public function __construct(string $eventId)
    {
        $this->eventId = $eventId;
    }

    public function denormalize($data, $class, $format = null, array $context = []): UpdateAttendanceMode
    {
        return new UpdateAttendanceMode(
            $this->eventId,
            new AttendanceMode($data['attendanceMode'])
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === UpdateAttendanceMode::class;
    }
}
