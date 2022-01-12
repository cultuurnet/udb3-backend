<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Curators;

use CultuurNet\UDB3\Curators\NewsArticle;
use CultuurNet\UDB3\Curators\NewsArticleRepository;
use CultuurNet\UDB3\Curators\NewsArticleSearch;
use CultuurNet\UDB3\Curators\Serializer\NewsArticleNormalizer;
use CultuurNet\UDB3\Http\Request\Headers;
use CultuurNet\UDB3\Http\Request\QueryParameters;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetNewsArticlesRequestHandler implements RequestHandlerInterface
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
        $queryParameters = new QueryParameters($request);

        $newsArticleSearch = new NewsArticleSearch(
            $queryParameters->get('publisher'),
            $queryParameters->get('about'),
            $queryParameters->get('url'),
        );

        $startPage = $queryParameters->get('page');
        if ($startPage) {
            $newsArticleSearch = $newsArticleSearch->withStartPage((int) $startPage);
        }

        $newsArticles = $this->newsArticleRepository->search($newsArticleSearch);

        $headers = new Headers($request);
        $responseContentType = $headers->determineResponseContentType(['application/ld+json', 'application/json']);
        $withJsonLd = $responseContentType === 'application/ld+json';

        $newsArticleNormalizer = $this->newsArticleNormalizer->asJsonLd($withJsonLd);

        $newsArticlesJson = array_map(
            fn (NewsArticle $newsArticle) => $newsArticleNormalizer->normalize($newsArticle),
            $newsArticles->toArray()
        );

        if ($withJsonLd) {
            return new JsonLdResponse(['hydra:member' => $newsArticlesJson]);
        }

        return new JsonResponse($newsArticlesJson);
    }
}
