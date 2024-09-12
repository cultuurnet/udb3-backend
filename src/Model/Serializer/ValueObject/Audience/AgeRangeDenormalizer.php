<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Audience;

use CultuurNet\UDB3\Model\ValueObject\Audience\Age;
use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class AgeRangeDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = []): AgeRange
    {
        $parts = $this->getParts($data, $class, $format);

        return new AgeRange($parts['from'], $parts['to']);
    }

    public function denormalizeFrom(string $data, string $class, ?string $format = null): ?Age
    {
        return $this->getParts($data, $class, $format)['from'];
    }

    public function denormalizeTo(string $data, string $class, ?string $format = null): ?Age
    {
        return $this->getParts($data, $class, $format)['to'];
    }

    private function getParts(string $data, string $class, ?string $format): array
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

        return [
            'from' => $from,
            'to' => $to,
        ];
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === AgeRange::class;
    }
}
