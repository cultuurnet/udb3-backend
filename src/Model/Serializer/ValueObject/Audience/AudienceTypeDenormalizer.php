<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Audience;

use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class AudienceTypeDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = []): AudienceType
    {
        return new AudienceType($data['audienceType']);
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return $data === AudienceType::class;
    }
}
