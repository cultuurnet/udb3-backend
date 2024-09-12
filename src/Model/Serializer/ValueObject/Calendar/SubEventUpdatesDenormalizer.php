<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEventUpdate;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEventUpdates;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class SubEventUpdatesDenormalizer implements DenormalizerInterface
{
    private SubEventUpdateDenormalizer $subEventUpdateDenormalizer;

    public function __construct()
    {
        $this->subEventUpdateDenormalizer = new SubEventUpdateDenormalizer();
    }

    public function denormalize($data, $class, $format = null, array $context = []): SubEventUpdates
    {
        $updates = [];
        foreach ($data as $subEventUpdateData) {
            $updates[] = $this->subEventUpdateDenormalizer->denormalize($subEventUpdateData, SubEventUpdate::class);
        }
        return new SubEventUpdates(...$updates);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === SubEventUpdates::class;
    }
}
