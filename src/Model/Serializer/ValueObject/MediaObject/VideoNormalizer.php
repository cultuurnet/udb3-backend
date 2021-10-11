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
    private const REGEX_FOR_PLATFORM = '/^http(s?):\/\/(www\.)?((youtube\.com\/watch\?v=([^\/#&?]*))|(vimeo\.com\/([^\/#&?]*)))/';

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
    ];

    /**
     * Associative array of default copyrightholders.
     * Key is the language, value is the translated string.
     *
     * @var string[]
     */
    private $defaultCopyrightHolders;

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
        $videoArray = [
            'id' => $video->getId()->toString(),
            'url' => $video->getUrl()->toString(),
            'embedUrl' => $this->createEmbedUrl($video->getUrl())->toString(),
            'language' => $video->getLanguage()->toString(),
        ];

        if ($video->getCopyrightHolder() !== null) {
            $videoArray['copyrightHolder'] = $video->getCopyrightHolder()->toString();
        } else {
            $videoArray['copyrightHolder'] = $this->createDefaultCopyrightHolder(
                $video->getLanguage(),
                $video->getUrl()
            )->toString();
        }

        return $videoArray;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data === Video::class;
    }

    private function getVideoData(Url $url): array
    {
        $matches = [];
        preg_match(
            self::REGEX_FOR_PLATFORM,
            $url->toString(),
            $matches
        );
        return $matches;
    }

    private function createEmbedUrl(Url $url): Url
    {
        $matches = $this->getVideoData($url);
        foreach ($this->videoPlatforms as $videoPlatformIndex => $videoPlatformData) {
            if (isset($matches[$videoPlatformIndex]) && !empty($matches[$videoPlatformIndex])) {
                return new Url($videoPlatformData['embed'] . $matches[$videoPlatformIndex]);
            }
        }
        throw new RuntimeException('Undefined Video Platform');
    }

    private function createDefaultCopyrightHolder(Language $language, Url $url): CopyrightHolder
    {
        $copyrightTranslation = $this->defaultCopyrightHolders[$language->toString()];
        $matches = $this->getVideoData($url);
        foreach ($this->videoPlatforms as $videoPlatformIndex => $videoPlatformData) {
            if (isset($matches[$videoPlatformIndex]) && !empty($matches[$videoPlatformIndex])) {
                return new CopyrightHolder($copyrightTranslation . ' ' . $videoPlatformData['name']);
            }
        }
        throw new RuntimeException('Unsupported Video Platform');
    }
}
