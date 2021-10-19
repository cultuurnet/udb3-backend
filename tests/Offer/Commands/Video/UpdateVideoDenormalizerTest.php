<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Video;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

class UpdateVideoDenormalizerTest extends TestCase
{
    private UpdateVideoDenormalizer $denormalizer;

    public function setUp()
    {
        $this->denormalizer = new UpdateVideoDenormalizer();
    }

    /**
     * @test
     */
    public function it_should_denormalize_with_all_values()
    {
        $videoData = [
            'id' => '9b5ce026-e200-4885-8b3b-396ecd879ebd',
            'copyrightHolder' => 'publiq',
            'language' => 'fr',
            'url' => 'https://www.youtube.com/watch?v=123',,
        ];

        $expected = (
            new UpdateVideo('9b5ce026-e200-4885-8b3b-396ecd879ebd')
        )->withCopyrightHolder(new CopyrightHolder('publiq'))
            ->withLanguage(new Language('fr'))
            ->withUrl(new Url('https://www.youtube.com/watch?v=123'));

        $actual = $this->denormalizer->denormalize($videoData, UpdateVideo::class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_denormalize_with_some_values_left_blank()
    {
        $videoData = [
            'id' => '9b5ce026-e200-4885-8b3b-396ecd879ebd',
            'copyrightHolder' => 'publiq',
        ];

        $expected = (new UpdateVideo('9b5ce026-e200-4885-8b3b-396ecd879ebd'))->withCopyrightHolder(new CopyrightHolder('publiq'));
        $actual = $this->denormalizer->denormalize($videoData, UpdateVideo::class);

        $this->assertEquals($expected, $actual);
    }
}
