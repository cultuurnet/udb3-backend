<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Audience;

use CultuurNet\UDB3\Model\ValueObject\Audience\BirthdateRange;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class BirthdateRangeNormalizer implements NormalizerInterface
{
    /**
     * @param BirthdateRange $birthdateRange
     */
    public function normalize($birthdateRange, $format = null, array $context = []): array
    {
        return [
            'from' => $birthdateRange->getFrom()->format('Y-m-d'),
            'to' => $birthdateRange->getTo()->format('Y-m-d'),
        ];
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof BirthdateRange;
    }
}
