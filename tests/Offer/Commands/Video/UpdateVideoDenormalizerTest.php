<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Video;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

final class UpdateVideoDenormalizerTest extends TestCase
{
    private UpdateVideoDenormalizer $denormalizer;

    private string $offerId = 'a7337d83-291e-4f41-827a-4513268cae90';

    public function setUp(): void
    {
        $this->denormalizer = new UpdateVideoDenormalizer($this->offerId);
    }

    /**
     * @test
     * @dataProvider updateVideoProvider
     */
    public function it_should_denormalize(UpdateVideo $updateVideo, array $updateVideoAsArray): void
    {
        $this->assertEquals(
            $updateVideo,
            $this->denormalizer->denormalize($updateVideoAsArray, UpdateVideo::class)
        );
    }

    public function updateVideoProvider(): array
    {
        $videoId = '208dbe98-ffaa-41cb-9ada-7ec8e0651f48';
        $updateVideo = new UpdateVideo($this->offerId, $videoId);

        return [
            'update_video_with_only_an_id' => [
                $updateVideo,
                [
                    'id' => $videoId,
                ],
            ],
            'update_video_with_some_values' => [
                $updateVideo->withCopyrightHolder(new CopyrightHolder('publiq')),
                [
                    'id' => $videoId,
                    'copyrightHolder' => 'publiq',
                ],
            ],
            'update_video_with_all_values' => [
                $updateVideo->withCopyrightHolder(new CopyrightHolder('publiq'))
                    ->withLanguage(new Language('fr'))
                    ->withUrl(new Url('https://www.youtube.com/watch?v=123')),
                [
                    'id' => $videoId,
                    'copyrightHolder' => 'publiq',
                    'language' => 'fr',
                    'url' => 'https://www.youtube.com/watch?v=123',
                ],
            ],
        ];
    }
}
