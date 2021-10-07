<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

final class VideoSerializerTest extends TestCase
{
    /**
     * @test
     * @dataProvider videoAddedProvider
     */
    public function it_can_serialize(Video $video, array $videoArray): void
    {
        $this->assertEquals(
            $videoArray,
            (new VideoSerializer())->serialize($video)
        );
    }

    public function videoAddedProvider(): array
    {
        return [
            'video_from_vimeo' => [
                new Video(
                    new UUID('5c549a24-bb97-4f83-8ea5-21a6d56aff72'),
                    new Url('https://vimeo.com/98765432'),
                    new Language('nl')
                ),
                [
                    'id' => '5c549a24-bb97-4f83-8ea5-21a6d56aff72',
                    'url' => 'https://vimeo.com/98765432',
                    'embedUrl' => 'https://player.vimeo.com/video/98765432',
                ],
            ],
            'video_from_youtube' => [
                new Video(
                    new UUID('91c75325-3830-4000-b580-5778b2de4548'),
                    new Url('https://www.youtube.com/watch?v=cEItmb_a20D'),
                    new Language('nl')
                ),
                [
                        'id' => '91c75325-3830-4000-b580-5778b2de4548',
                        'url' => 'https://www.youtube.com/watch?v=cEItmb_a20D',
                        'embedUrl' => 'https://www.youtube.com/embed/cEItmb_a20D',
                ],
            ],
        ];
    }
}
