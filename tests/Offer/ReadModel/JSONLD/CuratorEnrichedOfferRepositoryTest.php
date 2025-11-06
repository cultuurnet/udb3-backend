<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\Curators\NewsArticle;
use CultuurNet\UDB3\Curators\NewsArticleRepository;
use CultuurNet\UDB3\Curators\NewsArticles;
use CultuurNet\UDB3\Curators\NewsArticleSearch;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class CuratorEnrichedOfferRepositoryTest extends TestCase
{
    private CuratorEnrichedOfferRepository $curatorEnrichedOfferRepository;

    private NewsArticleRepository&MockObject $newsArticleRepository;

    protected function setUp(): void
    {
        $this->newsArticleRepository = $this->createMock(NewsArticleRepository::class);

        $this->curatorEnrichedOfferRepository = new CuratorEnrichedOfferRepository(
            new InMemoryDocumentRepository(),
            $this->newsArticleRepository,
            new NullLogger(),
            [
                'bill' => 'jongerenredactie',
                'bruzz' => 'BRUZZ-redactioneel',
                'indiestyle' => 'Indiestyle-redactioneel',
            ]
        );
    }

    /**
     * @test
     */
    public function it_applies_and_removes_curator_labels(): void
    {
        $id = '5624b810-c340-40a4-8f38-0393eca59bfe';
        $givenJson = [
            'labels' => ['foo', 'bar'],
            'hiddenLabels' => ['james', 'bond', 'BRUZZ-redactioneel'],
        ];
        $givenDocument = new JsonDocument($id, Json::encode($givenJson));

        $this->newsArticleRepository->expects($this->once())
            ->method('search')
            ->with(new NewsArticleSearch(null, $id, null))
            ->willReturn(
                new NewsArticles(
                    new NewsArticle(
                        new Uuid('1aa872e5-e0af-47a1-a9b3-31537b0cb4d9'),
                        'headline',
                        new Language('nl'),
                        'text',
                        $id,
                        'BILL',
                        new Url('https://www.publiq.be/blog'),
                        new Url('https://www.publiq.be/logo.png')
                    ),
                    new NewsArticle(
                        new Uuid('ad919c2e-c6ca-46b1-8f50-3b45f733342c'),
                        'headline',
                        new Language('nl'),
                        'text',
                        $id,
                        'indiestyle',
                        new Url('https://www.madewithlove.be/blog'),
                        new Url('https://www.madewithlove.be/logo.png')
                    )
                )
            );

        $this->curatorEnrichedOfferRepository->save($givenDocument);
        $actualDocument = $this->curatorEnrichedOfferRepository->fetch($id);
        $actualJson = $actualDocument->getAssocBody();

        $this->assertEquals(
            [
                'labels' => ['foo', 'bar'],
                'hiddenLabels' => ['james', 'bond', 'jongerenredactie', 'Indiestyle-redactioneel'],
            ],
            $actualJson
        );
    }

    /**
     * @test
     */
    public function it_handles_events_without_news_articles(): void
    {
        $id = '5624b810-c340-40a4-8f38-0393eca59bfe';
        $givenJson = [
            'labels' => ['foo', 'bar'],
            'hiddenLabels' => ['james', 'bond', 'BRUZZ-redactioneel'],
        ];
        $givenDocument = new JsonDocument($id, Json::encode($givenJson));

        $this->newsArticleRepository->expects($this->once())
            ->method('search')
            ->with(new NewsArticleSearch(null, $id, null))
            ->willReturn(
                new NewsArticles()
            );

        $this->curatorEnrichedOfferRepository->save($givenDocument);
        $actualDocument = $this->curatorEnrichedOfferRepository->fetch($id);
        $actualJson = $actualDocument->getAssocBody();

        $this->assertEquals(
            [
                'labels' => ['foo', 'bar'],
                'hiddenLabels' => ['james', 'bond'],
            ],
            $actualJson
        );
    }

    /**
     * @test
     */
    public function it_handles_events_without_hidden_labels(): void
    {
        $id = '5624b810-c340-40a4-8f38-0393eca59bfe';
        $givenJson = [
            'labels' => ['foo', 'bar'],
        ];
        $givenDocument = new JsonDocument($id, Json::encode($givenJson));

        $this->newsArticleRepository->expects($this->once())
            ->method('search')
            ->with(new NewsArticleSearch(null, $id, null))
            ->willReturn(
                new NewsArticles(
                    new NewsArticle(
                        new Uuid('1aa872e5-e0af-47a1-a9b3-31537b0cb4d9'),
                        'headline',
                        new Language('nl'),
                        'text',
                        $id,
                        'BILL',
                        new Url('https://www.publiq.be/blog'),
                        new Url('https://www.publiq.be/logo.png')
                    )
                )
            );

        $this->curatorEnrichedOfferRepository->save($givenDocument);
        $actualDocument = $this->curatorEnrichedOfferRepository->fetch($id);
        $actualJson = $actualDocument->getAssocBody();

        $this->assertEquals(
            [
                'labels' => ['foo', 'bar'],
                'hiddenLabels' => ['jongerenredactie'],
            ],
            $actualJson
        );
    }

    /**
     * @test
     */
    public function it_does_not_add_an_empty_list_of_hidden_labels(): void
    {
        $id = '5624b810-c340-40a4-8f38-0393eca59bfe';
        $givenJson = [
            'labels' => ['foo', 'bar'],
        ];
        $givenDocument = new JsonDocument($id, Json::encode($givenJson));

        $this->newsArticleRepository->expects($this->once())
            ->method('search')
            ->with(new NewsArticleSearch(null, $id, null))
            ->willReturn(
                new NewsArticles()
            );

        $this->curatorEnrichedOfferRepository->save($givenDocument);
        $actualDocument = $this->curatorEnrichedOfferRepository->fetch($id);
        $actualJson = $actualDocument->getAssocBody();

        $this->assertEquals(
            [
                'labels' => ['foo', 'bar'],
            ],
            $actualJson
        );
    }
}
