<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis\Trailer;

use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\SampleFiles;
use Google\Service\YouTube\Resource\Search;
use Google_Service_YouTube;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class YoutubeTrailerRepositoryTest extends TestCase
{
    private TrailerRepository $trailerRepository;

    private string $channelId;

    /**
     * @var UuidGeneratorInterface&MockObject
     */
    private $uuidGenerator;

    /**
     * @var Search&MockObject
     */
    private $search;

    public function setUp(): void
    {
        $this->search = $this->createMock(Search::class);
        $youtubeClient = $this->createMock(Google_Service_YouTube::class);
        $youtubeClient->search = $this->search;
        $this->channelId = 'mockChannelId';
        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);

        $this->trailerRepository = new YoutubeTrailerRepository(
            $youtubeClient,
            $this->channelId,
            $this->uuidGenerator,
            $this->createMock(LoggerInterface::class),
            true
        );
    }

    /**
     * @test
     */
    public function it_will_return_null_if_no_trailer_was_found(): void
    {
        $this->search->expects($this->once())->method('listSearch')->with('id,snippet', [
            'channelId' => $this->channelId,
            'q' => 'NotFound',
            'maxResults' => 1,
        ])->willReturn(
            Json::decodeAssociatively(
                SampleFiles::read(__DIR__ . '/../samples/YoutubeNoResults.json')
            )
        );
        $this->uuidGenerator->expects($this->never())->method('generate');

        $video = $this->trailerRepository->findMatchingTrailer('NotFound');
        $this->assertNull($video);
    }

    /**
     * @test
     */
    public function it_will_sanitize_searches(): void
    {
        $videoId = '37b04e81-42eb-4df7-8116-abe4217df426';
        $this->search->expects($this->once())->method('listSearch')->with('id,snippet', [
            'channelId' => $this->channelId,
            'q' => 'Visite+d%27%C3%A9quipe%3A+TKT%3A%7B',
            'maxResults' => 1,
        ])->willReturn(
            Json::decodeAssociatively(
                SampleFiles::read(__DIR__ . '/../samples/YoutubeSearchResult.json')
            )
        );
        $this->uuidGenerator->expects($this->once())->method('generate')->willReturn($videoId);

        $video = $this->trailerRepository->findMatchingTrailer('Visite d\'équipe: TKT:{');
        $this->assertEquals(
            new Video(
                $videoId,
                new Url('https://www.youtube.com/watch?v=26r2alNpYSg'),
                new Language('nl')
            ),
            $video
        );
    }
}
