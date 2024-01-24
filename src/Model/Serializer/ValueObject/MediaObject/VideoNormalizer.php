<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\VideoPlatformData;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class VideoNormalizer implements NormalizerInterface
{
    private array $defaultCopyrightHolders;

    /**
     * @param string[] $defaultCopyrightHolders
     */
    public function __construct(array $defaultCopyrightHolders)
    {
        $this->defaultCopyrightHolders = $defaultCopyrightHolders;
    }

    /**
     * @param Video $video
     */
    public function normalize($video, $format = null, array $context = []): array
    {
        if (!$video instanceof Video) {
            throw new InvalidArgumentException('Expected video object, got ' . get_class($video));
        }

        $platformData = VideoPlatformData::fromVideo($video);
        $videoArray = [
            'id' => $video->getId(),
            'url' => $video->getUrl()->toString(),
            'embedUrl' => $platformData['embedUrl'],
            'language' => $video->getLanguage()->toString(),
        ];

        if ($video->getCopyrightHolder() !== null) {
            $videoArray['copyrightHolder'] = $video->getCopyrightHolder()->toString();
            return $videoArray;
        }

        $videoArray['copyrightHolder'] = $this->createDefaultCopyrightHolder(
            $video->getLanguage(),
            $platformData
        )->toString();
        return $videoArray;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data === Video::class;
    }

    private function createDefaultCopyrightHolder(Language $language, array $platformData): CopyrightHolder
    {
        return new CopyrightHolder(
            sprintf($this->defaultCopyrightHolders[$language->toString()], $platformData['name'])
        );
    }
}
