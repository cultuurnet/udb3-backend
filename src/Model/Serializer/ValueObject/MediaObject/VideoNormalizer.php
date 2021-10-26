<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use RuntimeException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class VideoNormalizer implements NormalizerInterface
{
    private const YOUTUBE_EMBED = 'https://www.youtube.com/embed/';

    private const YOUTUBE_NAME = 'YouTube';

    private const VIMEO_EMBED = 'https://player.vimeo.com/video/';

    private const VIMEO_NAME = 'Vimeo';

    private array $videoPlatforms = [
        5 => [
            'embed' => self::YOUTUBE_EMBED,
            'name' => self::YOUTUBE_NAME,
            ],
        7 => [
            'embed' => self::VIMEO_EMBED,
            'name' => self::VIMEO_NAME,
        ],
        9 => [
            'embed' => self::YOUTUBE_EMBED,
            'name' => self::YOUTUBE_NAME,
        ],
    ];

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
        $platformData = $this->getPlatformData($video->getUrl());
        $videoArray = [
            'id' => $video->getId(),
            'url' => $video->getUrl()->toString(),
            'embedUrl' => $this->createEmbedUrl($platformData)->toString(),
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

    private function getPlatformData(Url $url): array
    {
        $matches = [];
        preg_match(
            Video::REGEX,
            $url->toString(),
            $matches
        );

        foreach ($this->videoPlatforms as $videoPlatformIndex => $videoPlatformData) {
            if (isset($matches[$videoPlatformIndex]) && !empty($matches[$videoPlatformIndex])) {
                return [
                    'embed' => $videoPlatformData['embed'],
                    'name' => $videoPlatformData['name'],
                    'video_id' => $matches[$videoPlatformIndex],
                ];
            }
        }
        throw new RuntimeException('Unsupported Video Platform');
    }

    private function createEmbedUrl(array $platformData): Url
    {
        return new Url($platformData['embed'] . $platformData['video_id']);
    }

    private function createDefaultCopyrightHolder(Language $language, array $platformData): CopyrightHolder
    {
        return new CopyrightHolder(
            sprintf($this->defaultCopyrightHolders[$language->toString()], $platformData['name'])
        );
    }
}
