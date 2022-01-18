<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Curators;

use CultuurNet\UDB3\Curators\NewsArticleRepository;
use CultuurNet\UDB3\Curators\NewsArticleSearch;
use CultuurNet\UDB3\Http\Request\QueryParameters;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetNewsArticlesRequestHandler implements RequestHandlerInterface
{
    private NewsArticleRepository $newsArticleRepository;

    public function __construct(
        NewsArticleRepository $newsArticleRepository
    ) {
        $this->newsArticleRepository = $newsArticleRepository;
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

        return (new NewsArticleResponseFactory($request))->createCollectionResponse(...$newsArticles);
    }
}
