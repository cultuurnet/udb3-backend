<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Video;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Symfony\Component\Serializer\Normalizer\DenormalizableInterface;

class UpdateVideoDenormalizer implements DenormalizableInterface
{
    private string $offerId;

    public function __construct(string $offerId)
    {
        $this->offerId = $offerId;
    }

    public function denormalize($data, $class, $format = null, array $context = []): UpdateVideo
    {
        $updateVideo = new UpdateVideo($this->offerId, $data['id']);

        if (isset($data['copyrightHolder'])) {
            $updateVideo = $updateVideo->withCopyrightHolder(
                new CopyrightHolder($data['copyrightHolder'])
            );
        }

        if (isset($data['language'])) {
            $updateVideo = $updateVideo->withLanguage(
                new Language($data['language'])
            );
        }

        if (isset($data['url'])) {
            $updateVideo = $updateVideo->withUrl(
                new Url($data['url'])
            );
        }

        return $updateVideo;
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === UpdateVideo::class;
    }
}
