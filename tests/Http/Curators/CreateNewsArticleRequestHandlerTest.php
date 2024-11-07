<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Curators;

use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Curators\NewsArticle;
use CultuurNet\UDB3\Curators\NewsArticleImage;
use CultuurNet\UDB3\Curators\NewsArticleRepository;
use CultuurNet\UDB3\Curators\NewsArticles;
use CultuurNet\UDB3\Curators\NewsArticleSearch;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreateNewsArticleRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    /** @var NewsArticleRepository&MockObject */
    private $newsArticleRepository;

    private CreateNewsArticleRequestHandler $createNewsArticleRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->newsArticleRepository = $this->createMock(NewsArticleRepository::class);

        /** @var UuidGeneratorInterface&MockObject $uuidGenerator */
        $uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $uuidGenerator->method('generate')
            ->willReturn('6c583739-a848-41ab-b8a3-8f7dab6f8ee1');

        $this->createNewsArticleRequestHandler = new CreateNewsArticleRequestHandler(
            $this->newsArticleRepository,
            $uuidGenerator,
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_creates_a_news_article_and_returns_jsonld_if_no_accept_header_is_given(): void
    {
        $createNewsArticleRequest = $this->psr7RequestBuilder
            ->withJsonBodyFromArray([
                'headline' => 'publiq wint API award',
                'inLanguage' => 'nl',
                'text' => 'Op 10 januari 2020 wint publiq de API award',
                'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                'publisher' => 'BILL',
                'url' => 'https://www.publiq.be/blog/api-reward',
                'publisherLogo' => 'https://www.bill.be/img/favicon.png',
            ])
            ->build('POST');

        $this->newsArticleRepository->expects($this->once())
            ->method('search')
            ->with(
                new NewsArticleSearch(
                    null,
                    '17284745-7bcf-461a-aad0-d3ad54880e75',
                    'https://www.publiq.be/blog/api-reward'
                )
            )
            ->willReturn(new NewsArticles());

        $this->newsArticleRepository->expects($this->once())
            ->method('create')
            ->with(new NewsArticle(
                new UUID('6c583739-a848-41ab-b8a3-8f7dab6f8ee1'),
                'publiq wint API award',
                new Language('nl'),
                'Op 10 januari 2020 wint publiq de API award',
                '17284745-7bcf-461a-aad0-d3ad54880e75',
                'BILL',
                new Url('https://www.publiq.be/blog/api-reward'),
                new Url('https://www.bill.be/img/favicon.png')
            ));

        $response = $this->createNewsArticleRequestHandler->handle($createNewsArticleRequest);

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
    public function it_creates_a_news_article_with_an_image(): void
    {
        $createNewsArticleRequest = $this->psr7RequestBuilder
            ->withJsonBodyFromArray([
                'headline' => 'publiq wint API award',
                'inLanguage' => 'nl',
                'text' => 'Op 10 januari 2020 wint publiq de API award',
                'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                'publisher' => 'BILL',
                'url' => 'https://www.publiq.be/blog/api-reward',
                'publisherLogo' => 'https://www.bill.be/img/favicon.png',
                'image' => [
                    'url' => 'https://www.uitinvlaanderen.be/img.png',
                    'copyrightHolder' => 'Publiq vzw',
                ],
            ])
            ->build('POST');

        $this->newsArticleRepository->expects($this->once())
            ->method('search')
            ->with(
                new NewsArticleSearch(
                    null,
                    '17284745-7bcf-461a-aad0-d3ad54880e75',
                    'https://www.publiq.be/blog/api-reward'
                )
            )
            ->willReturn(new NewsArticles());

        $this->newsArticleRepository->expects($this->once())
            ->method('create')
            ->with((new NewsArticle(
                new UUID('6c583739-a848-41ab-b8a3-8f7dab6f8ee1'),
                'publiq wint API award',
                new Language('nl'),
                'Op 10 januari 2020 wint publiq de API award',
                '17284745-7bcf-461a-aad0-d3ad54880e75',
                'BILL',
                new Url('https://www.publiq.be/blog/api-reward'),
                new Url('https://www.bill.be/img/favicon.png')
            ))->withImage(
                new NewsArticleImage(
                    new Url('https://www.uitinvlaanderen.be/img.png'),
                    new CopyrightHolder('Publiq vzw')
                )
            ));

        $response = $this->createNewsArticleRequestHandler->handle($createNewsArticleRequest);

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
                    'url' => 'https://www.uitinvlaanderen.be/img.png',
                    'copyrightHolder' => 'Publiq vzw',
                ],
            ]),
            $response->getBody()->getContents()
        );
    }

    /**
     * @test
     */
    public function it_creates_a_news_article_and_returns_jsonld_if_specified_in_accept_header(): void
    {
        $createNewsArticleRequest = $this->psr7RequestBuilder
            ->withHeader('accept', 'application/ld+json')
            ->withJsonBodyFromArray([
                'headline' => 'publiq wint API award',
                'inLanguage' => 'nl',
                'text' => 'Op 10 januari 2020 wint publiq de API award',
                'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                'publisher' => 'BILL',
                'url' => 'https://www.publiq.be/blog/api-reward',
                'publisherLogo' => 'https://www.bill.be/img/favicon.png',
            ])
            ->build('POST');

        $this->newsArticleRepository->expects($this->once())
            ->method('search')
            ->with(
                new NewsArticleSearch(
                    null,
                    '17284745-7bcf-461a-aad0-d3ad54880e75',
                    'https://www.publiq.be/blog/api-reward'
                )
            )
            ->willReturn(new NewsArticles());

        $this->newsArticleRepository->expects($this->once())
            ->method('create')
            ->with(new NewsArticle(
                new UUID('6c583739-a848-41ab-b8a3-8f7dab6f8ee1'),
                'publiq wint API award',
                new Language('nl'),
                'Op 10 januari 2020 wint publiq de API award',
                '17284745-7bcf-461a-aad0-d3ad54880e75',
                'BILL',
                new Url('https://www.publiq.be/blog/api-reward'),
                new Url('https://www.bill.be/img/favicon.png')
            ));

        $response = $this->createNewsArticleRequestHandler->handle($createNewsArticleRequest);

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
    public function it_creates_a_news_article_and_returns_json_if_specifically_requested_in_accept_header(): void
    {
        $createNewsArticleRequest = $this->psr7RequestBuilder
            ->withHeader('accept', 'application/json')
            ->withJsonBodyFromArray([
                'headline' => 'publiq wint API award',
                'inLanguage' => 'nl',
                'text' => 'Op 10 januari 2020 wint publiq de API award',
                'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                'publisher' => 'BILL',
                'url' => 'https://www.publiq.be/blog/api-reward',
                'publisherLogo' => 'https://www.bill.be/img/favicon.png',
            ])
            ->build('POST');

        $this->newsArticleRepository->expects($this->once())
            ->method('search')
            ->with(
                new NewsArticleSearch(
                    null,
                    '17284745-7bcf-461a-aad0-d3ad54880e75',
                    'https://www.publiq.be/blog/api-reward'
                )
            )
            ->willReturn(new NewsArticles());

        $this->newsArticleRepository->expects($this->once())
            ->method('create')
            ->with(new NewsArticle(
                new UUID('6c583739-a848-41ab-b8a3-8f7dab6f8ee1'),
                'publiq wint API award',
                new Language('nl'),
                'Op 10 januari 2020 wint publiq de API award',
                '17284745-7bcf-461a-aad0-d3ad54880e75',
                'BILL',
                new Url('https://www.publiq.be/blog/api-reward'),
                new Url('https://www.bill.be/img/favicon.png')
            ));

        $response = $this->createNewsArticleRequestHandler->handle($createNewsArticleRequest);

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
    public function it_creates_handles_creating_articles_with_urls_that_should_have_been_encoded(): void
    {
        $createNewsArticleRequest = $this->psr7RequestBuilder
            ->withJsonBodyFromArray([
                'headline' => 'publiq wint API award',
                'inLanguage' => 'nl',
                'text' => 'Op 10 januari 2020 wint publiq de API award',
                'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                'publisher' => 'BILL',
                'url' => 'https://www.publiq.be/café',
                'publisherLogo' => 'https://www.bill.be/img/favicon.png',
            ])
            ->build('POST');

        $this->newsArticleRepository->expects($this->once())
            ->method('search')
            ->with(
                new NewsArticleSearch(
                    null,
                    '17284745-7bcf-461a-aad0-d3ad54880e75',
                    'https://www.publiq.be/caf%C3%A9'
                )
            )
            ->willReturn(new NewsArticles());

        $this->newsArticleRepository->expects($this->once())
            ->method('create')
            ->with(new NewsArticle(
                new UUID('6c583739-a848-41ab-b8a3-8f7dab6f8ee1'),
                'publiq wint API award',
                new Language('nl'),
                'Op 10 januari 2020 wint publiq de API award',
                '17284745-7bcf-461a-aad0-d3ad54880e75',
                'BILL',
                new Url('https://www.publiq.be/caf%C3%A9'),
                new Url('https://www.bill.be/img/favicon.png')
            ));

        $response = $this->createNewsArticleRequestHandler->handle($createNewsArticleRequest);

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
                'url' => 'https://www.publiq.be/caf%C3%A9',
                'publisherLogo' => 'https://www.bill.be/img/favicon.png',
            ]),
            $response->getBody()->getContents()
        );
    }

    /**
     * @test
     */
    public function it_throws_if_a_news_article_with_the_same_url_and_about_already_exists(): void
    {
        $createNewsArticleRequest = $this->psr7RequestBuilder
            ->withHeader('accept', 'application/json')
            ->withJsonBodyFromArray([
                'headline' => 'publiq wint API award',
                'inLanguage' => 'nl',
                'text' => 'Op 10 januari 2020 wint publiq de API award',
                'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                'publisher' => 'BILL',
                'url' => 'https://www.publiq.be/blog/api-reward',
                'publisherLogo' => 'https://www.bill.be/img/favicon.png',
            ])
            ->build('POST');

        $this->newsArticleRepository->expects($this->once())
            ->method('search')
            ->with(
                new NewsArticleSearch(
                    null,
                    '17284745-7bcf-461a-aad0-d3ad54880e75',
                    'https://www.publiq.be/blog/api-reward'
                )
            )
            ->willReturn(
                new NewsArticles(
                    new NewsArticle(
                        new UUID('d684fc46-b0ba-4b64-9584-5f61fb5c4963'),
                        'Some other headline',
                        new Language('nl'),
                        'Some other text',
                        '17284745-7bcf-461a-aad0-d3ad54880e75',
                        'BILL',
                        new Url('https://www.publiq.be/blog/api-reward'),
                        new Url('https://www.bill.be/img/favicon.png')
                    )
                )
            );

        $this->newsArticleRepository->expects($this->never())
            ->method('create');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidDataWithDetail(
                'A news article with the given url and about already exists. (d684fc46-b0ba-4b64-9584-5f61fb5c4963) '
                . 'Do a GET /news-articles request with `url` and `about` parameters to find it programmatically.'
            ),
            fn () => $this->createNewsArticleRequestHandler->handle($createNewsArticleRequest)
        );
    }

    /**
     * @test
     */
    public function it_throws_on_empty_body(): void
    {
        $createNewsArticleRequest = $this->psr7RequestBuilder
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyMissing(),
            fn () => $this->createNewsArticleRequestHandler->handle($createNewsArticleRequest)
        );
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_body_syntax(): void
    {
        $createNewsArticleRequest = $this->psr7RequestBuilder
            ->withBodyFromString('{invalid}')
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidSyntax('JSON'),
            fn () => $this->createNewsArticleRequestHandler->handle($createNewsArticleRequest)
        );
    }

    /**
     * @test
     * @dataProvider invalidNewsArticleProviders
     */
    public function it_throws_on_missing_and_invalid_properties(array $body, ApiProblem $apiProblem): void
    {
        $createNewsArticleRequest = $this->psr7RequestBuilder
            ->withJsonBodyFromArray($body)
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            $apiProblem,
            fn () => $this->createNewsArticleRequestHandler->handle($createNewsArticleRequest)
        );
    }

    public function invalidNewsArticleProviders(): array
    {
        return [
            'missing headline' => [
                [
                    'inLanguage' => 'nl',
                    'text' => 'Op 10 januari 2020 wint publiq de API award',
                    'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                    'publisher' => 'BILL',
                    'url' => 'https://www.publiq.be/blog/api-reward',
                    'publisherLogo' => 'https://www.bill.be/img/favicon.png',
                ],
                ApiProblem::bodyInvalidData(
                    new SchemaError('/', 'The required properties (headline) are missing')
                ),
            ],
            'missing inLanguage' => [
                [
                    'headline' => 'publiq wint API award',
                    'text' => 'Op 10 januari 2020 wint publiq de API award',
                    'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                    'publisher' => 'BILL',
                    'url' => 'https://www.publiq.be/blog/api-reward',
                    'publisherLogo' => 'https://www.bill.be/img/favicon.png',
                ],
                ApiProblem::bodyInvalidData(
                    new SchemaError('/', 'The required properties (inLanguage) are missing')
                ),
            ],
            'various properties missing' => [
                [
                    'headline' => 'publiq wint API award',
                    'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                    'publisher' => 'BILL',
                    'publisherLogo' => 'https://www.bill.be/img/favicon.png',
                ],
                ApiProblem::bodyInvalidData(
                    new SchemaError('/', 'The required properties (inLanguage, text, url) are missing')
                ),
            ],
            'all properties missing' => [
                [
                    'missing' => 'all properties',
                ],
                ApiProblem::bodyInvalidData(
                    new SchemaError(
                        '/',
                        'The required properties (headline, inLanguage, text, about, publisher, publisherLogo, url) are missing'
                    )
                ),
            ],
            'invalid image url' => [
                [
                    'headline' => 'publiq wint API award',
                    'inLanguage' => 'nl',
                    'text' => 'Op 10 januari 2020 wint publiq de API award',
                    'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                    'publisher' => 'BILL',
                    'url' => 'https://www.publiq.be/blog/api-reward',
                    'publisherLogo' => 'https://www.bill.be/img/favicon.png',
                    'image' => [
                        'url' => 'https://www.uitinvlaanderen.be/img/manual.pdf',
                        'copyrightHolder' => 'Publiq vzw',
                    ],
                ],
                ApiProblem::bodyInvalidData(
                    new SchemaError(
                        '/image/url',
                        'The string should match pattern: ^http(s?):([/|.|\w|%20|-])*\.(?:jpeg|jpg|gif|png)$'
                    )
                ),
            ],
            'image without copyright' => [
                [
                    'headline' => 'publiq wint API award',
                    'inLanguage' => 'nl',
                    'text' => 'Op 10 januari 2020 wint publiq de API award',
                    'about' => '17284745-7bcf-461a-aad0-d3ad54880e75',
                    'publisher' => 'BILL',
                    'url' => 'https://www.publiq.be/blog/api-reward',
                    'publisherLogo' => 'https://www.bill.be/img/favicon.png',
                    'image' => [
                        'url' => 'https://www.uitinvlaanderen.be/img/setting.png',
                    ],
                ],
                ApiProblem::bodyInvalidData(
                    new SchemaError(
                        '/image',
                        'The required properties (copyrightHolder) are missing'
                    )
                ),
            ],
        ];
    }
}
