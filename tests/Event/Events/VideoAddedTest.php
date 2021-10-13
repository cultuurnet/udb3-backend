<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Item\Events;

use CultuurNet\UDB3\Event\Events\VideoAdded;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

final class VideoAddedTest extends TestCase
{
    /**
     * @test
     * @dataProvider videoAddedProvider
     */
    public function it_can_serialize(VideoAdded $videoAdded, array $videoAddedAsArray): void
    {
        $this->assertEquals(
            $videoAddedAsArray,
            $videoAdded->serialize()
        );
    }

    /**
     * @test
     * @dataProvider videoAddedProvider
     */
    public function it_can_deserialize(VideoAdded $videoAdded, array  $videoAddedAsArray): void
    {
        $this->assertEquals(
            $videoAdded,
            VideoAdded::deserialize($videoAddedAsArray)
        );
    }

    public function videoAddedProvider(): array
    {
        $video = new Video(
            '91c75325-3830-4000-b580-5778b2de4548',
            new Url('https://www.youtube.com/watch?v=123'),
            new Language('nl')
        );

        return [
            'video_with_copyright' => [
                new VideoAdded(
                    '208dbe98-ffaa-41cb-9ada-7ec8e0651f48',
                    $video->withCopyrightHolder(new CopyrightHolder('Creative Commons'))
                ),
                [
                    'item_id' => '208dbe98-ffaa-41cb-9ada-7ec8e0651f48',
                    'video' => [
                        'id' => '91c75325-3830-4000-b580-5778b2de4548',
                        'url' => 'https://www.youtube.com/watch?v=123',
                        'language' => 'nl',
                        'copyrightHolder' => 'Creative Commons',
                    ],
                ],
            ],
            'video_without_copyright' => [
                new VideoAdded(
                    '208dbe98-ffaa-41cb-9ada-7ec8e0651f48',
                    $video
                ),
                [
                    'item_id' => '208dbe98-ffaa-41cb-9ada-7ec8e0651f48',
                    'video' => [
                        'id' => '91c75325-3830-4000-b580-5778b2de4548',
                        'url' => 'https://www.youtube.com/watch?v=123',
                        'language' => 'nl',
                    ],
                ],
            ],

        ];
    }
}
