<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Video;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

class UpdateVideosDenormalizerTest extends TestCase
{
    private string $offerId = 'b20f171d-747a-46a3-8fd7-f7ab8ae11231';

    private UpdateVideosDenormalizer $denormalizer;

    public function setUp(): void
    {
        $this->denormalizer = new UpdateVideosDenormalizer(
            new UpdateVideoDenormalizer($this->offerId)
        );
    }

    /**
     * @test
     */
    public function it_can_denormalize_multiple_updates(): void
    {
        $videoData = [
            [
                'id' => '9b5ce026-e200-4885-8b3b-396ecd879ebd',
                'copyrightHolder' => 'publiq',
                'language' => 'fr',
                'url' => 'https://www.youtube.com/watch?v=123',
            ],
            [
                'id' => 'e16e3819-f63e-40c7-904e-80103b270a58',
                'copyrightHolder' => 'creative commons',
                'language' => 'nl',
                'url' => 'https://vimeo.com/98765432',
            ],
        ];

        $expected = new UpdateVideos(
            (new UpdateVideo($this->offerId, '9b5ce026-e200-4885-8b3b-396ecd879ebd'))
                ->withCopyrightHolder(new CopyrightHolder('publiq'))
                ->withLanguage(new Language('fr'))
                ->withUrl(new Url('https://www.youtube.com/watch?v=123')),
            (new UpdateVideo($this->offerId, 'e16e3819-f63e-40c7-904e-80103b270a58'))
                ->withCopyrightHolder(new CopyrightHolder('creative commons'))
                ->withLanguage(new Language('nl'))
                ->withUrl(new Url('https://vimeo.com/98765432')),
        );

        $actual = $this->denormalizer->denormalize($videoData, UpdateVideos::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_can_denormalize_a_single_updates(): void
    {
        $videoData = [
            [
                'id' => '9b5ce026-e200-4885-8b3b-396ecd879ebd',
                'copyrightHolder' => 'publiq',
                'language' => 'fr',
                'url' => 'https://www.youtube.com/watch?v=123',
            ],
        ];

        $expected = new UpdateVideos(
            (new UpdateVideo($this->offerId, '9b5ce026-e200-4885-8b3b-396ecd879ebd'))
                ->withCopyrightHolder(new CopyrightHolder('publiq'))
                ->withLanguage(new Language('fr'))
                ->withUrl(new Url('https://www.youtube.com/watch?v=123')),
        );

        $actual = $this->denormalizer->denormalize($videoData, UpdateVideos::class);

        $this->assertEquals($expected, $actual);
    }
}
