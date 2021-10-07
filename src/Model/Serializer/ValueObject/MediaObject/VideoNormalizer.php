<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use RuntimeException;

final class VideoNormalizer
{
    private const REGEX_FOR_PLATFORM = '/^http(s?):\/\/(www\.)?((youtube\.com\/watch\?v=([^\/#&?]*))|(vimeo\.com\/([^\/#&?]*)))/';

    private const YOUTUBE_EMBED = 'https://www.youtube.com/embed/';

    private const VIMEO_EMBED = 'https://player.vimeo.com/video/';

    private array $videoPlatforms = [
        5 => self::YOUTUBE_EMBED,
        7 => self::VIMEO_EMBED,
    ];

    public function serialize(Video $video): array
    {
        $videoArray = [
            'id' => $video->getId()->toString(),
            'url' => $video->getUrl()->toString(),
            'embedUrl' => $this->createEmbedUrl($video->getUrl())->toString(),
        ];

        if ($video->getCopyrightHolder() !== null) {
            $videoArray['copyrightHolder'] = $video->getCopyrightHolder()->toString();
        }

        return $videoArray;
    }

    private function createEmbedUrl(Url $url): Url
    {
        $matches = [];
        preg_match(
            self::REGEX_FOR_PLATFORM,
            $url->toString(),
            $matches
        );

        foreach ($this->videoPlatforms as $videoPlatformIndex => $videoPlatformEmbed) {
            if (isset($matches[$videoPlatformIndex]) && !empty($matches[$videoPlatformIndex])) {
                return new Url($videoPlatformEmbed . $matches[$videoPlatformIndex]);
            }
        }
        throw new RuntimeException('Undefined Video Platform');
    }
}
