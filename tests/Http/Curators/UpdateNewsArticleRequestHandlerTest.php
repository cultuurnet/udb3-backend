<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Curators;

use CultuurNet\UDB3\Curators\NewsArticle;
use CultuurNet\UDB3\Curators\NewsArticleImage;
use CultuurNet\UDB3\Curators\NewsArticleNotFound;
use CultuurNet\UDB3\Curators\NewsArticleRepository;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UpdateNewsArticleRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    /** @var NewsArticleRepository&MockObject */
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
    public function it_updates_a_news_article_and_returns_jsonld_if_no_accept_header_is_given(): void
    {
        $createOrganizerRequest = $this->psr7RequestBuilder
            ->withRouteParameter('articleId', '6c583739-a848-41ab-b8a3-8f7dab6f8ee1')
            ->withJsonBodyFromArray([
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
            new Uuid('6c583739-a848-41ab-b8a3-8f7dab6f8ee1'),
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
            ->with(new Uuid('6c583739-a848-41ab-b8a3-8f7dab6f8ee1'))
            ->willReturn($newsArticle);

        $this->newsArticleRepository->expects($this->once())
            ->method('update')
            ->with($newsArticle);

        $response = $this->updateNewsArticleRequestHandler->handle($createOrganizerRequest);

        $this->assertEquals(
            Json::encode([
                '@context' => '/contexts/NewsArticle',
                '@id' => '/news-articles/6c583739-a848-41ab-b8a3-8f7dab6f8ee1',
                '@type' => 'https://schema.org/NewsArticle',
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
    public function it_updates_a_news_article_and_returns_jsonld_if_present_in_accept_header(): void
    {
        $updateNewsArticleRequest = $this->psr7RequestBuilder
            ->withHeader('accept', 'application/ld+json')
            ->withRouteParameter('articleId', '6c583739-a848-41ab-b8a3-8f7dab6f8ee1')
            ->withJsonBodyFromArray([
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
            new Uuid('6c583739-a848-41ab-b8a3-8f7dab6f8ee1'),
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
            ->with(new Uuid('6c583739-a848-41ab-b8a3-8f7dab6f8ee1'))
            ->willReturn($newsArticle);

        $this->newsArticleRepository->expects($this->once())
            ->method('update')
            ->with($newsArticle);

        $response = $this->updateNewsArticleRequestHandler->handle($updateNewsArticleRequest);

        $this->assertEquals(
            Json::encode([
                '@context' => '/contexts/NewsArticle',
                '@id' => '/news-articles/6c583739-a848-41ab-b8a3-8f7dab6f8ee1',
                '@type' => 'https://schema.org/NewsArticle',
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
    public function it_updates_a_news_article_and_returns_json_if_specifically_requested(): void
    {
        $updateNewsArticleRequest = $this->psr7RequestBuilder
            ->withHeader('accept', 'application/json')
            ->withRouteParameter('articleId', '6c583739-a848-41ab-b8a3-8f7dab6f8ee1')
            ->withJsonBodyFromArray([
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
            new Uuid('6c583739-a848-41ab-b8a3-8f7dab6f8ee1'),
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
            ->with(new Uuid('6c583739-a848-41ab-b8a3-8f7dab6f8ee1'))
            ->willReturn($newsArticle);

        $this->newsArticleRepository->expects($this->once())
            ->method('update')
            ->with($newsArticle);

        $response = $this->updateNewsArticleRequestHandler->handle($updateNewsArticleRequest);

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
    public function it_updates_a_news_article_with_an_url_that_should_have_been_encoded(): void
    {
        $updateNewsArticleRequest = $this->psr7RequestBuilder
            ->withRouteParameter('articleId', '6c583739-a848-41ab-b8a3-8f7dab6f8ee1')
            ->withJsonBodyFromArray([
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
            new Uuid('6c583739-a848-41ab-b8a3-8f7dab6f8ee1'),
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
            ->with(new Uuid('6c583739-a848-41ab-b8a3-8f7dab6f8ee1'))
            ->willReturn($newsArticle);

        $this->newsArticleRepository->expects($this->once())
            ->method('update')
            ->with($newsArticle);

        $response = $this->updateNewsArticleRequestHandler->handle($updateNewsArticleRequest);

        $this->assertEquals(
            Json::encode([
                '@context' => '/contexts/NewsArticle',
                '@id' => '/news-articles/6c583739-a848-41ab-b8a3-8f7dab6f8ee1',
                '@type' => 'https://schema.org/NewsArticle',
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
    public function it_updates_a_news_article_with_an_image(): void
    {
        $updateNewsArticleRequest = $this->psr7RequestBuilder
            ->withRouteParameter('articleId', '6c583739-a848-41ab-b8a3-8f7dab6f8ee1')
            ->withJsonBodyFromArray([
                'headline' => 'publiq wint API award',
                'inLanguage' => 'nl',
                'text' => 'Op 10 januari 2020 wint publiq de API award',
                'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                'publisher' => 'BILL',
                'url' => 'https://www.publiq.be/blog/api-reward',
                'publisherLogo' => 'https://www.bill.be/img/favicon.png',
                'image' => [
                    'url' => 'https://www.publiq.be/assets/logo.png',
                    'copyrightHolder' => 'Publiq vzw',
                ],
            ])
            ->build('PUT');

        $newsArticle = new NewsArticle(
            new Uuid('6c583739-a848-41ab-b8a3-8f7dab6f8ee1'),
            'publiq wint API award',
            new Language('nl'),
            'Op 10 januari 2020 wint publiq de API award',
            '17284745-7bcf-461a-aad0-d3ad54880e75',
            'BILL',
            new Url('https://www.publiq.be/blog/api-reward'),
            new Url('https://www.bill.be/img/favicon.png')
        );

        $updateNewsArticle = $newsArticle->withImage(
            new NewsArticleImage(
                new Url('https://www.publiq.be/assets/logo.png'),
                new CopyrightHolder('Publiq vzw')
            )
        );

        $this->newsArticleRepository->expects($this->once())
            ->method('getById')
            ->with(new Uuid('6c583739-a848-41ab-b8a3-8f7dab6f8ee1'))
            ->willReturn($newsArticle);

        $this->newsArticleRepository->expects($this->once())
            ->method('update')
            ->with($updateNewsArticle);

        $response = $this->updateNewsArticleRequestHandler->handle($updateNewsArticleRequest);

        $this->assertEquals(
            Json::encode([
                '@context' => '/contexts/NewsArticle',
                '@id' => '/news-articles/6c583739-a848-41ab-b8a3-8f7dab6f8ee1',
                '@type' => 'https://schema.org/NewsArticle',
                'id' => '6c583739-a848-41ab-b8a3-8f7dab6f8ee1',
                'headline' => 'publiq wint API award',
                'inLanguage' => 'nl',
                'text' => 'Op 10 januari 2020 wint publiq de API award',
                'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                'publisher' => 'BILL',
                'url' => 'https://www.publiq.be/blog/api-reward',
                'publisherLogo' => 'https://www.bill.be/img/favicon.png',
                'image' => [
                    'url' => 'https://www.publiq.be/assets/logo.png',
                    'copyrightHolder' => 'Publiq vzw',
                ],
            ]),
            $response->getBody()->getContents()
        );
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_image_url(): void
    {
        $updateNewsArticleRequest = $this->psr7RequestBuilder
            ->withRouteParameter('articleId', '6c583739-a848-41ab-b8a3-8f7dab6f8ee1')
            ->withJsonBodyFromArray([
                'headline' => 'publiq wint API award',
                'inLanguage' => 'nl',
                'text' => 'Op 10 januari 2020 wint publiq de API award',
                'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                'publisher' => 'BILL',
                'url' => 'https://www.publiq.be/blog/api-reward',
                'publisherLogo' => 'https://www.bill.be/img/favicon.png',
                'image' => [
                    'url' => 'https://www.publiq.be/assets/logo.pdf',
                    'copyrightHolder' => 'Publiq vzw',
                ],
            ])
            ->build('PUT');

        $newsArticle = new NewsArticle(
            new Uuid('6c583739-a848-41ab-b8a3-8f7dab6f8ee1'),
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
            ->with(new Uuid('6c583739-a848-41ab-b8a3-8f7dab6f8ee1'))
            ->willReturn($newsArticle);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/image/url', 'The string should match pattern: ^http(s?):([/|.|\w|%20|-])*\.(?:jpeg|jpg|gif|png)$')
            ),
            fn () => $this->updateNewsArticleRequestHandler->handle($updateNewsArticleRequest)
        );
    }

    /**
     * @test
     */
    public function it_does_not_allow_an_image_without_copyright(): void
    {
        $updateNewsArticleRequest = $this->psr7RequestBuilder
            ->withRouteParameter('articleId', '6c583739-a848-41ab-b8a3-8f7dab6f8ee1')
            ->withJsonBodyFromArray([
                'headline' => 'publiq wint API award',
                'inLanguage' => 'nl',
                'text' => 'Op 10 januari 2020 wint publiq de API award',
                'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                'publisher' => 'BILL',
                'url' => 'https://www.publiq.be/blog/api-reward',
                'publisherLogo' => 'https://www.bill.be/img/favicon.png',
                'image' => [
                    'url' => 'https://www.publiq.be/assets/logo.jpeg',
                ],
            ])
            ->build('PUT');

        $newsArticle = new NewsArticle(
            new Uuid('6c583739-a848-41ab-b8a3-8f7dab6f8ee1'),
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
            ->with(new Uuid('6c583739-a848-41ab-b8a3-8f7dab6f8ee1'))
            ->willReturn($newsArticle);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/image', 'The required properties (copyrightHolder) are missing')
            ),
            fn () => $this->updateNewsArticleRequestHandler->handle($updateNewsArticleRequest)
        );
    }

    /**
     * @test
     */
    public function it_throws_when_news_article_not_found(): void
    {
        $updateNewsArticleRequest = $this->psr7RequestBuilder
            ->withRouteParameter('articleId', '6c583739-a848-41ab-b8a3-8f7dab6f8ee1')
            ->build('UPDATE');

        $this->newsArticleRepository->expects($this->once())
            ->method('getById')
            ->willThrowException(new NewsArticleNotFound(new Uuid('6c583739-a848-41ab-b8a3-8f7dab6f8ee1')));

        $this->assertCallableThrowsApiProblem(
            ApiProblem::newsArticleNotFound('6c583739-a848-41ab-b8a3-8f7dab6f8ee1'),
            fn () => $this->updateNewsArticleRequestHandler->handle($updateNewsArticleRequest)
        );
    }
}
