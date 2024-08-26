<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis\Trailer;

use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use Google\Service\YouTube\Resource\Search;
use Google_Service_YouTube;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class YoutubeTrailerRepositoryTest extends TestCase
{
    private TrailerRepository $trailerRepository;

    /**
     * @var Google_Service_YouTube&MockObject
     */
    private $youtubeClient;

    private string $channelId;

    /**
     * @var UuidGeneratorInterface&MockObject
     */
    private $uuidGenerator;

    public function setUp(): void
    {
        $this->youtubeClient = $this->createMock(Google_Service_YouTube::class);
        $this->channelId = 'mockChannelId';
        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $this->trailerRepository = new YoutubeTrailerRepository(
            $this->youtubeClient,
            $this->channelId,
            $this->uuidGenerator,
            true
        );
    }

    /**
     * @test
     */
    public function it_will_only_search_when_enabled(): void
    {
        $disabledTrailerRepository = new YoutubeTrailerRepository(
            $this->youtubeClient,
            $this->channelId,
            $this->uuidGenerator,
            false
        );

        $this->youtubeClient->expects($this->never())->method('search');
        $result = $disabledTrailerRepository->search('Het Smelt');
        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function it_will_sanitize_searches(): void
    {
        $search = $this->createMock(Search::class);

        $this->youtubeClient->expects($this->once())->method('search')->willReturn($search);
        $search->expects($this->once())->method('listSearch')->with('id,snippet', [
            'channelId' => $this->channelId,
            'q' => 'Visite%20d%27%C3%A9quipe%3A%20TKT%3A%7B',
            'maxResults' => 1,
        ]);
        $this->trailerRepository->search('Visite d\'équipe: TKT:{');
    }
}
