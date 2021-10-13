<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use RuntimeException;
use PHPUnit\Framework\TestCase;

final class VideoNormalizerTest extends TestCase
{
    /**
     * @test
     * @dataProvider videoAddedProvider
     */
    public function it_can_serialize(Video $video, array $videoArray): void
    {
        $this->assertEquals(
            $videoArray,
            (new VideoNormalizer([
                'nl' => 'Copyright afgehandeld door %s',
                'fr' => 'Droits d\'auteur gérés par %s',
                'de' => 'Urheberrecht gehandhabt von %s',
                'en' => 'Copyright handled by %s',
            ]))->normalize($video)
        );
    }

    /**
     * @test
     */
    public function it_throws_an_error_for_unsupported_video_platforms(): void
    {
        $this->expectException(RuntimeException::class);

        (new VideoNormalizer([
            'nl' => 'Copyright afgehandeld door %s',
            'fr' => 'Droits d\'auteur gérés par %s',
            'de' => 'Urheberrecht gehandhabt von %s',
            'en' => 'Copyright handled by %s',
        ]))->normalize(new Video(
            '6fad3c7e-2a7f-4957-94a1-8009bb6b7de4',
            new Url('https://myspace.com/myspace/video/publiq/901564992'),
            new Language('nl')
        ));
    }

    public function videoAddedProvider(): array
    {
        return [
            'video_from_vimeo' => [
                new Video(
                    '5c549a24-bb97-4f83-8ea5-21a6d56aff72',
                    new Url('https://vimeo.com/98765432'),
                    new Language('nl')
                ),
                [
                    'id' => '5c549a24-bb97-4f83-8ea5-21a6d56aff72',
                    'url' => 'https://vimeo.com/98765432',
                    'embedUrl' => 'https://player.vimeo.com/video/98765432',
                    'language' => 'nl',
                    'copyrightHolder' => 'Copyright afgehandeld door Vimeo',
                ],
            ],
            'video_from_youtube' => [
                new Video(
                    '91c75325-3830-4000-b580-5778b2de4548',
                    new Url('https://www.youtube.com/watch?v=cEItmb_a20D'),
                    new Language('fr')
                ),
                [
                    'id' => '91c75325-3830-4000-b580-5778b2de4548',
                    'url' => 'https://www.youtube.com/watch?v=cEItmb_a20D',
                    'embedUrl' => 'https://www.youtube.com/embed/cEItmb_a20D',
                    'language' => 'fr',
                    'copyrightHolder' => 'Droits d\'auteur gérés par YouTube',
                ],
            ],
        ];
    }
}
