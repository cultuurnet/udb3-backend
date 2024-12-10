<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Curators;

use CultuurNet\UDB3\Curators\NewsArticleNotFound;
use CultuurNet\UDB3\Curators\NewsArticleRepository;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetNewsArticleRequestHandler implements RequestHandlerInterface
{
    private NewsArticleRepository $newsArticleRepository;

    public function __construct(
        NewsArticleRepository $newsArticleRepository
    ) {
        $this->newsArticleRepository = $newsArticleRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $articleId = $routeParameters->get('articleId');

        try {
            $newsArticle = $this->newsArticleRepository->getById(new Uuid($articleId));
        } catch (NewsArticleNotFound $exception) {
            throw ApiProblem::newsArticleNotFound($articleId);
        }

        return (new NewsArticleResponseFactory($request))->createResourceResponse($newsArticle);
    }
}
