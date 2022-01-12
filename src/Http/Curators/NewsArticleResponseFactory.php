<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Curators;

use CultuurNet\UDB3\Curators\NewsArticle;
use CultuurNet\UDB3\Curators\Serializer\NewsArticleNormalizer;
use CultuurNet\UDB3\Http\Request\Headers;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class NewsArticleResponseFactory
{
    private const POSSIBLE_MEDIA_TYPES = [
        'application/ld+json',
        'application/json',
    ];

    private string $mediaType;
    private NewsArticleNormalizer $normalizer;

    public function __construct(ServerRequestInterface $request)
    {
        $headers = new Headers($request);
        $this->mediaType = $headers->determineResponseContentType(self::POSSIBLE_MEDIA_TYPES);
        $this->normalizer = (new NewsArticleNormalizer())
            ->asJsonLd($this->asJsonLd());
    }

    public function createResourceResponse(
        NewsArticle $newsArticle,
        int $status = StatusCodeInterface::STATUS_OK
    ): ResponseInterface {
        $resource = $this->normalizer->normalize($newsArticle);
        return $this->createResponse($resource, $status);
    }

    public function createCollectionResponse(NewsArticle ...$newsArticles): ResponseInterface
    {
        $collection = array_map(
            fn (NewsArticle $newsArticle) => $this->normalizer->normalize($newsArticle),
            $newsArticles
        );

        if ($this->asJsonLd()) {
            $collection = ['hydra:member' => $collection];
        }

        return $this->createResponse($collection, StatusCodeInterface::STATUS_OK);
    }

    private function asJsonLd(): bool
    {
        return $this->mediaType === 'application/ld+json';
    }

    private function createResponse($data, int $status): ResponseInterface
    {
        if ($this->asJsonLd()) {
            return new JsonLdResponse($data, $status);
        }
        return new JsonResponse($data, $status);
    }
}
