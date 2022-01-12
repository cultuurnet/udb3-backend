<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Curators;

use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Curators\NewsArticle;
use CultuurNet\UDB3\Curators\NewsArticleRepository;
use CultuurNet\UDB3\Curators\Serializer\NewsArticleDenormalizer;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
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
        $uuid = new UUID($this->uuidGenerator->generate());

        $requestBodyParser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::NEWS_ARTICLE_POST),
            new DenormalizingRequestBodyParser(
                new NewsArticleDenormalizer($uuid),
                NewsArticle::class
            )
        );

        /** @var NewsArticle $newsArticle */
        $newsArticle = $requestBodyParser->parse($request)->getParsedBody();

        $this->newsArticleRepository->create($newsArticle);

        return (new NewsArticleResponseFactory($request))
            ->createResourceResponse($newsArticle, StatusCodeInterface::STATUS_CREATED);
    }
}
