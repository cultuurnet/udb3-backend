<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use RuntimeException;

final class VideoPlatformData
{
    private const YOUTUBE_EMBED = 'https://www.youtube.com/embed/';

    private const YOUTUBE_SHORT = 'https://www.youtube.com/shorts/';

    private const YOUTUBE_NAME = 'YouTube';

    private const VIMEO_EMBED = 'https://player.vimeo.com/video/';

    private const VIMEO_NAME = 'Vimeo';

    private static array $videoPlatforms = [
        // This index is the group number from the matching regexp from Video::REGEX
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
        11 => [
            'embed' => self::YOUTUBE_EMBED,
            'name' => self::YOUTUBE_NAME,
        ],
        13 => [
            'embed' => self::YOUTUBE_SHORT,
            'name' => self::YOUTUBE_NAME,
        ],
    ];

    public static function fromVideo(Video $video): array
    {
        $matches = [];
        preg_match(
            Video::REGEX,
            $video->getUrl()->toString(),
            $matches
        );

        foreach (self::$videoPlatforms as $videoPlatformIndex => $videoPlatformData) {
            if (!empty($matches[$videoPlatformIndex])) {
                return [
                    'embed' => $videoPlatformData['embed'],
                    'name' => $videoPlatformData['name'],
                    'video_id' => $matches[$videoPlatformIndex],
                    'embedUrl' => $videoPlatformData['embed'] . $matches[$videoPlatformIndex],
                ];
            }
        }
        throw new RuntimeException('Unsupported Video Platform');
    }
}
