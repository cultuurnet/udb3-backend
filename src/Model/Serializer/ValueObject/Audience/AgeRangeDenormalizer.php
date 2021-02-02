<?php

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Audience;

use CultuurNet\UDB3\Model\ValueObject\Audience\Age;
use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class AgeRangeDenormalizer implements DenormalizerInterface
{
    /**
     * @inheritdoc
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new UnsupportedException("AgeRangeDenormalizer does not support {$class}.");
        }

        $regex = '/\\A([\\d]*)-([\\d]*)\\z/';
        $parts = [];
        preg_match($regex, $data, $parts);

        $from = $parts[1];
        $to = $parts[2];

        $from = empty($from) ? new Age(0) : new Age((int) $from);
        $to = empty($to) ? null : new Age((int) $to);

        return new AgeRange($from, $to);
    }

    /**
     * @inheritdoc
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === AgeRange::class;
    }
}
