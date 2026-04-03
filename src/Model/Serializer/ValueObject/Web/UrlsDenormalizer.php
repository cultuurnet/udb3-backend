<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Web;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UrlsDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $type, $format = null, array $context = []): Urls
    {
        $urls = array_map(
            fn (string $url) => new Url($url),
            $data
        );

        return new Urls(...$urls);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return is_a($type, Urls::class, true);
    }
}
