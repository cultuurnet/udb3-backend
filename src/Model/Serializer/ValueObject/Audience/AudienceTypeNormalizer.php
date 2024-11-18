<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Audience;

use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class AudienceTypeNormalizer implements NormalizerInterface
{
    /**
     * @param AudienceType $audienceType
     */
    public function normalize($audienceType, $format = null, array $context = []): array
    {
        return [
            'audienceType' => $audienceType->toString(),
        ];
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data === AudienceType::class;
    }
}
