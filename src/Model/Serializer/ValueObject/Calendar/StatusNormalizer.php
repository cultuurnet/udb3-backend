<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class StatusNormalizer implements NormalizerInterface
{
    /**
     * @param Status $status
     */
    public function normalize($status, $format = null, array $context = []): array
    {
        $serialized = [
            'type' => $status->getType()->toString(),
        ];

        if ($status->getReason() === null) {
            return $serialized;
        }

        $statusReasons = [];
        foreach ($status->getReason()->getLanguages() as $language) {
            $statusReasons[$language->getCode()] = $status->getReason()->getTranslation($language)->toString();
        }
        $serialized['reason'] = $statusReasons;

        return $serialized;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data === Status::class;
    }
}
