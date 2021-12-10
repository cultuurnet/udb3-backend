<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Curators;

use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Curators\NewsArticle;
use CultuurNet\UDB3\Curators\NewsArticleRepository;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateNewsArticleRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    /** @var NewsArticleRepository|MockObject */
    private $newsArticleRepository;

    private CreateNewsArticleRequestHandler $createNewsArticleRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->newsArticleRepository = $this->createMock(NewsArticleRepository::class);

        /** @var UuidGeneratorInterface|MockObject $uuidGenerator */
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
    public function it_creates_a_news_article(): void
    {
        $createOrganizerRequest = $this->psr7RequestBuilder
            ->withBodyFromArray([
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

        $response = $this->createNewsArticleRequestHandler->handle($createOrganizerRequest);

        $this->assertEquals(
            '{"id":"6c583739-a848-41ab-b8a3-8f7dab6f8ee1"}',
            $response->getBody()->getContents()
        );
    }

    /**
     * @test
     */
    public function it_throws_on_empty_body(): void
    {
        $createOrganizerRequest = $this->psr7RequestBuilder
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyMissing(),
            fn () => $this->createNewsArticleRequestHandler->handle($createOrganizerRequest)
        );
    }

    /**
     * @test
     */
    public function it_throws_on_invalid_body_syntax(): void
    {
        $createOrganizerRequest = $this->psr7RequestBuilder
            ->withBodyFromString('{invalid}')
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidSyntax('JSON'),
            fn () => $this->createNewsArticleRequestHandler->handle($createOrganizerRequest)
        );
    }

    /**
     * @test
     * @dataProvider invalidNewsArticleProviders
     */
    public function it_throws_on_missing_properties(array $body, ApiProblem $apiProblem): void
    {
        $createOrganizerRequest = $this->psr7RequestBuilder
            ->withBodyFromArray($body)
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            $apiProblem,
            fn () => $this->createNewsArticleRequestHandler->handle($createOrganizerRequest)
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
        ];
    }
}
