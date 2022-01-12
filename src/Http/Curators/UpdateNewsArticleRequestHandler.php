<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Curators;

use CultuurNet\UDB3\Curators\NewsArticle;
use CultuurNet\UDB3\Curators\NewsArticleNotFound;
use CultuurNet\UDB3\Curators\NewsArticleRepository;
use CultuurNet\UDB3\Curators\Serializer\NewsArticleDenormalizer;
use CultuurNet\UDB3\Curators\Serializer\NewsArticleNormalizer;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\Headers;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateNewsArticleRequestHandler implements RequestHandlerInterface
{
    private NewsArticleRepository $newsArticleRepository;

    public function __construct(NewsArticleRepository $newsArticleRepository)
    {
        $this->newsArticleRepository = $newsArticleRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $articleId = $routeParameters->get('articleId');

        try {
            $this->newsArticleRepository->getById(new UUID($articleId));
        } catch (NewsArticleNotFound $exception) {
            throw ApiProblem::newsArticleNotFound($articleId);
        }

        $requestBodyParser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::NEWS_ARTICLE_POST),
            new DenormalizingRequestBodyParser(
                new NewsArticleDenormalizer(new UUID($articleId)),
                NewsArticle::class
            )
        );

        /** @var NewsArticle $newsArticle */
        $newsArticle = $requestBodyParser->parse($request)->getParsedBody();

        $this->newsArticleRepository->update($newsArticle);

        $headers = new Headers($request);
        $responseContentType = $headers->determineResponseContentType(['application/ld+json', 'application/json']);
        $withJsonLd = $responseContentType === 'application/ld+json';

        $normalized = (new NewsArticleNormalizer())->asJsonLd($withJsonLd)->normalize($newsArticle);

        if ($withJsonLd) {
            return new JsonLdResponse($normalized);
        }
        return new JsonResponse($normalized);
    }
}
