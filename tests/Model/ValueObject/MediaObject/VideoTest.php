<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

class VideoTest extends TestCase
{
    /**
     * @dataProvider videoDataProvider
     * @test
     */
    public function it_can_compare_videos(Video $video1, Video $video2, bool $same): void
    {
        $this->assertEquals($video1->sameAs($video2), $same);
    }

    public function videoDataProvider(): array
    {
        return [
            'Different id' => [
                (new Video(
                    'f1e27cec-8912-4275-b6bc-7013a727d75c',
                    new Url('https://www.youtube.com/watch?v=123'),
                    new Language('nl')
                ))->withCopyrightHolder(new CopyrightHolder('publiq')),
                (new Video(
                    '71558258-437b-4757-b155-7d0576cbb3ae',
                    new Url('https://www.youtube.com/watch?v=123'),
                    new Language('nl')
                ))->withCopyrightHolder(new CopyrightHolder('publiq')),
                false
            ],
            'Different url' => [
                (new Video(
                    'f1e27cec-8912-4275-b6bc-7013a727d75c',
                    new Url('https://www.youtube.com/watch?v=123'),
                    new Language('nl')
                ))->withCopyrightHolder(new CopyrightHolder('publiq')),
                (new Video(
                    'f1e27cec-8912-4275-b6bc-7013a727d75c',
                    new Url('https://www.vimeo.com/123'),
                    new Language('nl')
                ))->withCopyrightHolder(new CopyrightHolder('publiq')),
                false
            ],
            'Different language' => [
                (new Video(
                    'f1e27cec-8912-4275-b6bc-7013a727d75c',
                    new Url('https://www.youtube.com/watch?v=123'),
                    new Language('nl')
                ))->withCopyrightHolder(new CopyrightHolder('publiq')),
                (new Video(
                    'f1e27cec-8912-4275-b6bc-7013a727d75c',
                    new Url('https://www.youtube.com/watch?v=123'),
                    new Language('fr')
                ))->withCopyrightHolder(new CopyrightHolder('publiq')),
                false
            ],
            'Different copyright, first empty' => [
                new Video(
                    'f1e27cec-8912-4275-b6bc-7013a727d75c',
                    new Url('https://www.youtube.com/watch?v=123'),
                    new Language('nl')
                ),
                (new Video(
                    'f1e27cec-8912-4275-b6bc-7013a727d75c',
                    new Url('https://www.youtube.com/watch?v=123'),
                    new Language('nl')
                ))->withCopyrightHolder(new CopyrightHolder('publiq')),
                false
            ],
            'Different copyright, second empty' => [
                (new Video(
                    'f1e27cec-8912-4275-b6bc-7013a727d75c',
                    new Url('https://www.youtube.com/watch?v=123'),
                    new Language('nl')
                ))->withCopyrightHolder(new CopyrightHolder('publiq')),
                new Video(
                    'f1e27cec-8912-4275-b6bc-7013a727d75c',
                    new Url('https://www.youtube.com/watch?v=123'),
                    new Language('nl')
                ),
                false
            ],
            'Different copyright' => [
                (new Video(
                    'f1e27cec-8912-4275-b6bc-7013a727d75c',
                    new Url('https://www.youtube.com/watch?v=123'),
                    new Language('nl')
                ))->withCopyrightHolder(new CopyrightHolder('publiq')),
                (new Video(
                    'f1e27cec-8912-4275-b6bc-7013a727d75c',
                    new Url('https://www.youtube.com/watch?v=123'),
                    new Language('nl')
                ))->withCopyrightHolder(new CopyrightHolder('madewithlove')),
                false
            ],
            'Videos are the same' => [
                (new Video(
                    'f1e27cec-8912-4275-b6bc-7013a727d75c',
                    new Url('https://www.youtube.com/watch?v=123'),
                    new Language('nl')
                ))->withCopyrightHolder(new CopyrightHolder('publiq')),
                (new Video(
                    'f1e27cec-8912-4275-b6bc-7013a727d75c',
                    new Url('https://www.youtube.com/watch?v=123'),
                    new Language('nl')
                ))->withCopyrightHolder(new CopyrightHolder('publiq')),
                true
            ],
        ];
    }
}
