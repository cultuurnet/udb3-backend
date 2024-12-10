<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Curators;

use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Curators\NewsArticle;
use CultuurNet\UDB3\Curators\NewsArticleRepository;
use CultuurNet\UDB3\Curators\NewsArticleSearch;
use CultuurNet\UDB3\Curators\Serializer\NewsArticleDenormalizer;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CreateNewsArticleRequestHandler implements RequestHandlerInterface
{
    private NewsArticleRepository $newsArticleRepository;

    private UuidGeneratorInterface $uuidGenerator;

    public function __construct(NewsArticleRepository $newsArticleRepository, UuidGeneratorInterface $uuidGenerator)
    {
        $this->newsArticleRepository = $newsArticleRepository;
        $this->uuidGenerator = $uuidGenerator;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = new Uuid($this->uuidGenerator->generate());

        $requestBodyParser = RequestBodyParserFactory::createBaseParser(
            new UrlEncodingRequestBodyParser(),
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::NEWS_ARTICLE_POST),
            new DenormalizingRequestBodyParser(
                new NewsArticleDenormalizer($uuid),
                NewsArticle::class
            )
        );

        /** @var NewsArticle $newsArticle */
        $newsArticle = $requestBodyParser->parse($request)->getParsedBody();

        // Do not include publisher to search for duplicates, because each publisher already has its own URL anyway.
        $existingNewsArticles = $this->newsArticleRepository->search(
            new NewsArticleSearch(
                null,
                $newsArticle->getAbout(),
                $newsArticle->getUrl()->toString()
            )
        );

        if (!$existingNewsArticles->isEmpty()) {
            /** @var NewsArticle $first */
            $first = $existingNewsArticles->getFirst();
            $id = $first->getId()->toString();

            throw ApiProblem::bodyInvalidDataWithDetail(
                'A news article with the given url and about already exists. (' . $id . ') '
                    . 'Do a GET /news-articles request with `url` and `about` parameters to find it programmatically.'
            );
        }

        $this->newsArticleRepository->create($newsArticle);

        return (new NewsArticleResponseFactory($request))
            ->createResourceResponse($newsArticle, StatusCodeInterface::STATUS_CREATED);
    }
}
