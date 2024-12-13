<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Curators;

use CultuurNet\UDB3\Curators\NewsArticle;
use CultuurNet\UDB3\Curators\NewsArticleImage;
use CultuurNet\UDB3\Curators\NewsArticleNotFound;
use CultuurNet\UDB3\Curators\NewsArticleRepository;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetNewsArticleRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    /** @var NewsArticleRepository&MockObject  */
    private $newsArticleRepository;

    private GetNewsArticleRequestHandler $getNewsArticleRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->newsArticleRepository = $this->createMock(NewsArticleRepository::class);
        $this->getNewsArticleRequestHandler = new GetNewsArticleRequestHandler($this->newsArticleRepository);
        $this->psr7RequestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_handles_getting_a_news_article_with_jsonld_if_no_accept_header_is_given(): void
    {
        $articleId = new Uuid('ec00bcd0-41e9-47a0-8364-71aad7e537c5');

        $newsArticle = new NewsArticle(
            $articleId,
            'publiq wint API award',
            new Language('nl'),
            'Op 10 januari 2020 wint publiq de API award',
            '17284745-7bcf-461a-aad0-d3ad54880e75',
            'BILL',
            new Url('https://www.publiq.be/blog/api-reward'),
            new Url('https://www.bill.be/img/favicon.png')
        );

        $getNewsArticleRequest = $this->psr7RequestBuilder
            ->withRouteParameter('articleId', $articleId->toString())
            ->build('GET');

        $this->newsArticleRepository->expects($this->once())
            ->method('getById')
            ->with($articleId)
            ->willReturn($newsArticle);

        $response = $this->getNewsArticleRequestHandler->handle($getNewsArticleRequest);

        $this->assertEquals(
            Json::encode([
                '@context' => '/contexts/NewsArticle',
                '@id' => '/news-articles/' . $articleId->toString(),
                '@type' => 'https://schema.org/NewsArticle',
                'id' => $articleId->toString(),
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
    public function it_handles_getting_a_news_article_with_an_image(): void
    {
        $articleId = new Uuid('ec00bcd0-41e9-47a0-8364-71aad7e537c5');

        $newsArticle = (new NewsArticle(
            $articleId,
            'publiq wint API award',
            new Language('nl'),
            'Op 10 januari 2020 wint publiq de API award',
            '17284745-7bcf-461a-aad0-d3ad54880e75',
            'BILL',
            new Url('https://www.publiq.be/blog/api-reward'),
            new Url('https://www.bill.be/img/favicon.png')
        ))->withImage(
            new NewsArticleImage(
                new Url('https://www.publiq.be/afbeelding.gif'),
                new CopyrightHolder('Publiq vzw')
            )
        );

        $getNewsArticleRequest = $this->psr7RequestBuilder
            ->withRouteParameter('articleId', $articleId->toString())
            ->build('GET');

        $this->newsArticleRepository->expects($this->once())
            ->method('getById')
            ->with($articleId)
            ->willReturn($newsArticle);

        $response = $this->getNewsArticleRequestHandler->handle($getNewsArticleRequest);

        $this->assertEquals(
            Json::encode([
                '@context' => '/contexts/NewsArticle',
                '@id' => '/news-articles/' . $articleId->toString(),
                '@type' => 'https://schema.org/NewsArticle',
                'id' => $articleId->toString(),
                'headline' => 'publiq wint API award',
                'inLanguage' => 'nl',
                'text' => 'Op 10 januari 2020 wint publiq de API award',
                'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                'publisher' => 'BILL',
                'url' => 'https://www.publiq.be/blog/api-reward',
                'publisherLogo' => 'https://www.bill.be/img/favicon.png',
                'image' => [
                    'url' => 'https://www.publiq.be/afbeelding.gif',
                    'copyrightHolder' => 'Publiq vzw',
                ],
            ]),
            $response->getBody()->getContents()
        );
    }

    /**
     * @test
     */
    public function it_handles_getting_a_news_article_with_jsonld_if_accept_header_includes_jsonld(): void
    {
        $articleId = new Uuid('ec00bcd0-41e9-47a0-8364-71aad7e537c5');

        $newsArticle = new NewsArticle(
            $articleId,
            'publiq wint API award',
            new Language('nl'),
            'Op 10 januari 2020 wint publiq de API award',
            '17284745-7bcf-461a-aad0-d3ad54880e75',
            'BILL',
            new Url('https://www.publiq.be/blog/api-reward'),
            new Url('https://www.bill.be/img/favicon.png')
        );

        $getNewsArticleRequest = $this->psr7RequestBuilder
            ->withRouteParameter('articleId', $articleId->toString())
            ->withHeader('accept', 'application/ld+json')
            ->build('GET');

        $this->newsArticleRepository->expects($this->once())
            ->method('getById')
            ->with($articleId)
            ->willReturn($newsArticle);

        $response = $this->getNewsArticleRequestHandler->handle($getNewsArticleRequest);

        $this->assertEquals(
            Json::encode([
                '@context' => '/contexts/NewsArticle',
                '@id' => '/news-articles/' . $articleId->toString(),
                '@type' => 'https://schema.org/NewsArticle',
                'id' => $articleId->toString(),
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
    public function it_handles_getting_a_news_article_with_json_if_accept_header_specifically_requests_json(): void
    {
        $articleId = new Uuid('ec00bcd0-41e9-47a0-8364-71aad7e537c5');

        $newsArticle = new NewsArticle(
            $articleId,
            'publiq wint API award',
            new Language('nl'),
            'Op 10 januari 2020 wint publiq de API award',
            '17284745-7bcf-461a-aad0-d3ad54880e75',
            'BILL',
            new Url('https://www.publiq.be/blog/api-reward'),
            new Url('https://www.bill.be/img/favicon.png')
        );

        $getNewsArticleRequest = $this->psr7RequestBuilder
            ->withRouteParameter('articleId', $articleId->toString())
            ->withHeader('accept', 'application/json')
            ->build('GET');

        $this->newsArticleRepository->expects($this->once())
            ->method('getById')
            ->with($articleId)
            ->willReturn($newsArticle);

        $response = $this->getNewsArticleRequestHandler->handle($getNewsArticleRequest);

        $this->assertEquals(
            Json::encode([
                'id' => $articleId->toString(),
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
        $articleId = new Uuid('ec00bcd0-41e9-47a0-8364-71aad7e537c5');

        $getNewsArticleRequest = $this->psr7RequestBuilder
            ->withRouteParameter('articleId', $articleId->toString())
            ->build('GET');

        $this->newsArticleRepository->expects($this->once())
            ->method('getById')
            ->with($articleId)
            ->willThrowException(new NewsArticleNotFound($articleId));

        $this->assertCallableThrowsApiProblem(
            ApiProblem::newsArticleNotFound($articleId->toString()),
            fn () => $this->getNewsArticleRequestHandler->handle($getNewsArticleRequest)
        );
    }
}
