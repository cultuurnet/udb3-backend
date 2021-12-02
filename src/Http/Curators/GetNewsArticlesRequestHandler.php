<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Curators;

use CultuurNet\UDB3\Curators\NewsArticle;
use CultuurNet\UDB3\Curators\NewsArticleRepository;
use CultuurNet\UDB3\Curators\Serializer\NewsArticleNormalizer;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
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
        $newsArticles = $this->newsArticleRepository->getAll();

        return new JsonLdResponse(
            array_map(
                fn (NewsArticle $newsArticle) => $this->newsArticleNormalizer->normalize($newsArticle),
                $newsArticles->toArray()
            )
        );
    }
}
