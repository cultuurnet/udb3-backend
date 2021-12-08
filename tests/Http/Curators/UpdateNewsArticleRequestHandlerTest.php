<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Curators;

use CultuurNet\UDB3\Curators\NewsArticle;
use CultuurNet\UDB3\Curators\NewsArticleNotFound;
use CultuurNet\UDB3\Curators\NewsArticleRepository;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateNewsArticleRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    /** @var NewsArticleRepository|MockObject */
    private $newsArticleRepository;

    private UpdateNewsArticleRequestHandler $updateNewsArticleRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->newsArticleRepository = $this->createMock(NewsArticleRepository::class);

        $this->updateNewsArticleRequestHandler = new UpdateNewsArticleRequestHandler(
            $this->newsArticleRepository,
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_updates_a_news_article(): void
    {
        $createOrganizerRequest = $this->psr7RequestBuilder
            ->withRouteParameter('articleId', '6c583739-a848-41ab-b8a3-8f7dab6f8ee1')
            ->withBodyFromArray([
                'headline' => 'publiq wint API award',
                'inLanguage' => 'nl',
                'text' => 'Op 10 januari 2020 wint publiq de API award',
                'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                'publisher' => 'BILL',
                'url' => 'https://www.publiq.be/blog/api-reward',
                'publisherLogo' => 'https://www.bill.be/img/favicon.png',
            ])
            ->build('PUT');

        $newsArticle = new NewsArticle(
            new UUID('6c583739-a848-41ab-b8a3-8f7dab6f8ee1'),
            'publiq wint API award',
            new Language('nl'),
            'Op 10 januari 2020 wint publiq de API award',
            '17284745-7bcf-461a-aad0-d3ad54880e75',
            'BILL',
            new Url('https://www.publiq.be/blog/api-reward'),
            new Url('https://www.bill.be/img/favicon.png')
        );

        $this->newsArticleRepository->expects($this->once())
            ->method('getById')
            ->with(new UUID('6c583739-a848-41ab-b8a3-8f7dab6f8ee1'))
            ->willReturn($newsArticle);

        $this->newsArticleRepository->expects($this->once())
            ->method('update')
            ->with($newsArticle);

        $response = $this->updateNewsArticleRequestHandler->handle($createOrganizerRequest);

        $this->assertEquals(
            Json::encode([
                'id' => '6c583739-a848-41ab-b8a3-8f7dab6f8ee1',
                'headline' => 'publiq wint API award',
                'inLanguage' => 'nl',
                'text' => 'Op 10 januari 2020 wint publiq de API award',
                'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                'publisher' => 'BILL',
                'url' => 'https://www.publiq.be/blog/api-reward',
                'publisherLogo' => 'https://www.bill.be/img/favicon.png',
            ]),
            $response->getBody()->getContents()
        );
    }

    /**
     * @test
     */
    public function it_throws_when_news_article_not_found(): void
    {
        $updateOrganizerRequest = $this->psr7RequestBuilder
            ->withRouteParameter('articleId', '6c583739-a848-41ab-b8a3-8f7dab6f8ee1')
            ->build('UPDATE');

        $this->newsArticleRepository->expects($this->once())
            ->method('getById')
            ->willThrowException(new NewsArticleNotFound(new UUID('6c583739-a848-41ab-b8a3-8f7dab6f8ee1')));

        $this->assertCallableThrowsApiProblem(
            ApiProblem::newsArticleNotFound('6c583739-a848-41ab-b8a3-8f7dab6f8ee1'),
            fn () => $this->updateNewsArticleRequestHandler->handle($updateOrganizerRequest)
        );
    }
}
