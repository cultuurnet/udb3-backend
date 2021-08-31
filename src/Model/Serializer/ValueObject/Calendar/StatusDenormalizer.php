<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedStatusReason;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class StatusDenormalizer implements DenormalizerInterface
{
    private TranslatedStatusReasonDenormalizer $statusReasonDenormalizer;

    public function __construct()
    {
        $this->statusReasonDenormalizer = new TranslatedStatusReasonDenormalizer();
    }

    public function denormalize($data, $class, $format = null, array $context = []): Status
    {
        $statusType = new StatusType($data['type']);
        $statusReason = null;

        if (isset($data['reason']) && !empty($data['reason'])) {
            $statusReason = $this->statusReasonDenormalizer->denormalize(
                $data['reason'],
                TranslatedStatusReason::class
            );
        }

        return new Status($statusType, $statusReason);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === Status::class;
    }
}
