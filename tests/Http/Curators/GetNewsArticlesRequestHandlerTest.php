<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Curators;

use CultuurNet\UDB3\Curators\NewsArticle;
use CultuurNet\UDB3\Curators\NewsArticleRepository;
use CultuurNet\UDB3\Curators\NewsArticles;
use CultuurNet\UDB3\Curators\NewsArticleSearch;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetNewsArticlesRequestHandlerTest extends TestCase
{
    /** @var NewsArticleRepository&MockObject  */
    private $newsArticleRepository;

    private GetNewsArticlesRequestHandler $getNewsArticlesRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->newsArticleRepository = $this->createMock(NewsArticleRepository::class);
        $this->getNewsArticlesRequestHandler = new GetNewsArticlesRequestHandler($this->newsArticleRepository);
        $this->psr7RequestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_handles_getting_all_news_articles_as_jsonld_without_accept_header(): void
    {
        $newsArticle1 = new NewsArticle(
            new UUID('ec00bcd0-41e9-47a0-8364-71aad7e537c5'),
            'publiq wint API award',
            new Language('nl'),
            'Op 10 januari 2020 wint publiq de API award',
            '17284745-7bcf-461a-aad0-d3ad54880e75',
            'BILL',
            new Url('https://www.publiq.be/blog/api-reward'),
            new Url('https://www.bill.be/img/favicon.png')
        );

        $newsArticle2 = new NewsArticle(
            new UUID('9bf7f5fa-4a0b-4475-9ebb-f776e33510f5'),
            'madewithlove creates API',
            new Language('en'),
            'Together with publiq madewithlove creates an API',
            '17284745-7bcf-461a-aad0-d3ad54880e75',
            'BUZZ',
            new Url('https://www.buzz.be/blog/api'),
            new Url('https://www.buzz.be/img/favicon.png')
        );

        $getNewsArticlesRequest = $this->psr7RequestBuilder
            ->build('GET');

        $this->newsArticleRepository->expects($this->once())
            ->method('search')
            ->willReturn(new NewsArticles($newsArticle1, $newsArticle2));

        $response = $this->getNewsArticlesRequestHandler->handle($getNewsArticlesRequest);

        $this->assertEquals(
            Json::encode([
                'hydra:member' => [
                    [
                        '@context' => '/contexts/NewsArticle',
                        '@id' => '/news-articles/ec00bcd0-41e9-47a0-8364-71aad7e537c5',
                        '@type' => 'https://schema.org/NewsArticle',
                        'id' => 'ec00bcd0-41e9-47a0-8364-71aad7e537c5',
                        'headline' => 'publiq wint API award',
                        'inLanguage' => 'nl',
                        'text' => 'Op 10 januari 2020 wint publiq de API award',
                        'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                        'publisher' => 'BILL',
                        'url' => 'https://www.publiq.be/blog/api-reward',
                        'publisherLogo' => 'https://www.bill.be/img/favicon.png',
                    ],
                    [
                        '@context' => '/contexts/NewsArticle',
                        '@id' => '/news-articles/9bf7f5fa-4a0b-4475-9ebb-f776e33510f5',
                        '@type' => 'https://schema.org/NewsArticle',
                        'id' => '9bf7f5fa-4a0b-4475-9ebb-f776e33510f5',
                        'headline' => 'madewithlove creates API',
                        'inLanguage' => 'en',
                        'text' => 'Together with publiq madewithlove creates an API',
                        'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                        'publisher' => 'BUZZ',
                        'url' => 'https://www.buzz.be/blog/api',
                        'publisherLogo' => 'https://www.buzz.be/img/favicon.png',
                    ],
                ],
            ]),
            $response->getBody()->getContents()
        );
    }

    /**
     * @test
     */
    public function it_handles_getting_all_news_articles_as_jsonld_with_accept_header_that_includes_jsonld(): void
    {
        $newsArticle1 = new NewsArticle(
            new UUID('ec00bcd0-41e9-47a0-8364-71aad7e537c5'),
            'publiq wint API award',
            new Language('nl'),
            'Op 10 januari 2020 wint publiq de API award',
            '17284745-7bcf-461a-aad0-d3ad54880e75',
            'BILL',
            new Url('https://www.publiq.be/blog/api-reward'),
            new Url('https://www.bill.be/img/favicon.png')
        );

        $newsArticle2 = new NewsArticle(
            new UUID('9bf7f5fa-4a0b-4475-9ebb-f776e33510f5'),
            'madewithlove creates API',
            new Language('en'),
            'Together with publiq madewithlove creates an API',
            '17284745-7bcf-461a-aad0-d3ad54880e75',
            'BUZZ',
            new Url('https://www.buzz.be/blog/api'),
            new Url('https://www.buzz.be/img/favicon.png')
        );

        $getNewsArticlesRequest = $this->psr7RequestBuilder
            ->withHeader('accept', 'application/ld+json')
            ->build('GET');

        $this->newsArticleRepository->expects($this->once())
            ->method('search')
            ->willReturn(new NewsArticles($newsArticle1, $newsArticle2));

        $response = $this->getNewsArticlesRequestHandler->handle($getNewsArticlesRequest);

        $this->assertEquals(
            Json::encode([
                'hydra:member' => [
                    [
                        '@context' => '/contexts/NewsArticle',
                        '@id' => '/news-articles/ec00bcd0-41e9-47a0-8364-71aad7e537c5',
                        '@type' => 'https://schema.org/NewsArticle',
                        'id' => 'ec00bcd0-41e9-47a0-8364-71aad7e537c5',
                        'headline' => 'publiq wint API award',
                        'inLanguage' => 'nl',
                        'text' => 'Op 10 januari 2020 wint publiq de API award',
                        'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                        'publisher' => 'BILL',
                        'url' => 'https://www.publiq.be/blog/api-reward',
                        'publisherLogo' => 'https://www.bill.be/img/favicon.png',
                    ],
                    [
                        '@context' => '/contexts/NewsArticle',
                        '@id' => '/news-articles/9bf7f5fa-4a0b-4475-9ebb-f776e33510f5',
                        '@type' => 'https://schema.org/NewsArticle',
                        'id' => '9bf7f5fa-4a0b-4475-9ebb-f776e33510f5',
                        'headline' => 'madewithlove creates API',
                        'inLanguage' => 'en',
                        'text' => 'Together with publiq madewithlove creates an API',
                        'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                        'publisher' => 'BUZZ',
                        'url' => 'https://www.buzz.be/blog/api',
                        'publisherLogo' => 'https://www.buzz.be/img/favicon.png',
                    ],
                ],
            ]),
            $response->getBody()->getContents()
        );
    }

    /**
     * @test
     */
    public function it_handles_getting_all_news_articles_as_json_with_accept_header_that_requests_json(): void
    {
        $newsArticle1 = new NewsArticle(
            new UUID('ec00bcd0-41e9-47a0-8364-71aad7e537c5'),
            'publiq wint API award',
            new Language('nl'),
            'Op 10 januari 2020 wint publiq de API award',
            '17284745-7bcf-461a-aad0-d3ad54880e75',
            'BILL',
            new Url('https://www.publiq.be/blog/api-reward'),
            new Url('https://www.bill.be/img/favicon.png')
        );

        $newsArticle2 = new NewsArticle(
            new UUID('9bf7f5fa-4a0b-4475-9ebb-f776e33510f5'),
            'madewithlove creates API',
            new Language('en'),
            'Together with publiq madewithlove creates an API',
            '17284745-7bcf-461a-aad0-d3ad54880e75',
            'BUZZ',
            new Url('https://www.buzz.be/blog/api'),
            new Url('https://www.buzz.be/img/favicon.png')
        );

        $getNewsArticlesRequest = $this->psr7RequestBuilder
            ->withHeader('accept', 'application/json')
            ->build('GET');

        $this->newsArticleRepository->expects($this->once())
            ->method('search')
            ->willReturn(new NewsArticles($newsArticle1, $newsArticle2));

        $response = $this->getNewsArticlesRequestHandler->handle($getNewsArticlesRequest);

        $this->assertEquals(
            Json::encode([
                [
                    'id' => 'ec00bcd0-41e9-47a0-8364-71aad7e537c5',
                    'headline' => 'publiq wint API award',
                    'inLanguage' => 'nl',
                    'text' => 'Op 10 januari 2020 wint publiq de API award',
                    'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                    'publisher' => 'BILL',
                    'url' => 'https://www.publiq.be/blog/api-reward',
                    'publisherLogo' => 'https://www.bill.be/img/favicon.png',
                ],
                [
                    'id' => '9bf7f5fa-4a0b-4475-9ebb-f776e33510f5',
                    'headline' => 'madewithlove creates API',
                    'inLanguage' => 'en',
                    'text' => 'Together with publiq madewithlove creates an API',
                    'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                    'publisher' => 'BUZZ',
                    'url' => 'https://www.buzz.be/blog/api',
                    'publisherLogo' => 'https://www.buzz.be/img/favicon.png',
                ],
            ]),
            $response->getBody()->getContents()
        );
    }

    /**
     * @test
     */
    public function it_handles_search_parameters(): void
    {
        $getNewsArticlesRequest = $this->psr7RequestBuilder
            ->withUriFromString(
                '?publisher=BILL&about=17284745-7bcf-461a-aad0-d3ad54880e75&url=https://www.buzz.be/blog/api&page=3'
            )
            ->build('GET');

        $this->newsArticleRepository->expects($this->once())
            ->method('search')
            ->with((new NewsArticleSearch(
                'BILL',
                '17284745-7bcf-461a-aad0-d3ad54880e75',
                'https://www.buzz.be/blog/api'
            ))->withStartPage(3))
            ->willReturn(new NewsArticles());

        $this->getNewsArticlesRequestHandler->handle($getNewsArticlesRequest);
    }

    /**
     * @test
     */
    public function it_returns_empty_list_when_no_articles_present(): void
    {
        $getNewsArticlesRequest = $this->psr7RequestBuilder
            ->build('GET');

        $this->newsArticleRepository->expects($this->once())
            ->method('search')
            ->willReturn(new NewsArticles());

        $response = $this->getNewsArticlesRequestHandler->handle($getNewsArticlesRequest);

        $this->assertEquals(
            Json::encode(['hydra:member' => []]),
            $response->getBody()->getContents()
        );
    }
}
