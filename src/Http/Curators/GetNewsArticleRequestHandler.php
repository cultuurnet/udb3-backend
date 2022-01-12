<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Curators;

use CultuurNet\UDB3\Curators\NewsArticleNotFound;
use CultuurNet\UDB3\Curators\NewsArticleRepository;
use CultuurNet\UDB3\Curators\Serializer\NewsArticleNormalizer;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Headers;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetNewsArticleRequestHandler implements RequestHandlerInterface
{
    private NewsArticleRepository $newsArticleRepository;

    private NewsArticleNormalizer $newsArticleNormalizer;

    public function __construct(
        NewsArticleRepository $newsArticleRepository,
        NewsArticleNormalizer $newsArticleNormalizer
    ) {
        $this->newsArticleRepository = $newsArticleRepository;
        $this->newsArticleNormalizer = $newsArticleNormalizer;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $articleId = $routeParameters->get('articleId');

        try {
            $newsArticle = $this->newsArticleRepository->getById(new UUID($articleId));
        } catch (NewsArticleNotFound $exception) {
            throw ApiProblem::newsArticleNotFound($articleId);
        }

        $headers = new Headers($request);
        $responseContentType = $headers->determineResponseContentType(['application/ld+json', 'application/json']);
        $withJsonLd = $responseContentType === 'application/ld+json';

        $normalized = $this->newsArticleNormalizer->asJsonLd($withJsonLd)->normalize($newsArticle);

        if ($withJsonLd) {
            return new JsonLdResponse($normalized);
        }
        return new JsonResponse($normalized);
    }
}
