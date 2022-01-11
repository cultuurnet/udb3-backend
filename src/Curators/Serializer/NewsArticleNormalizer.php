<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators\Serializer;

use CultuurNet\UDB3\Curators\NewsArticle;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class NewsArticleNormalizer implements NormalizerInterface
{
    private $jsonLd = false;

    public function withJsonLd(): self
    {
        $c = clone $this;
        $c->jsonLd = true;
        return $c;
    }

    /**
     * @param NewsArticle $newsArticle
     */
    public function normalize($newsArticle, $format = null, array $context = []): array
    {
        $data = [];

        if ($this->jsonLd) {
            $data += [
                '@context' => '/contexts/NewsArticle',
                '@id' => '/news-articles/' . $newsArticle->getId()->toString(),
                '@type' => 'https://schema.org/NewsArticle',
            ];
        }

        $data += [
            'id' => $newsArticle->getId()->toString(),
            'headline' => $newsArticle->getHeadline(),
            'inLanguage' => $newsArticle->getLanguage()->toString(),
            'text' => $newsArticle->getText(),
            'about' => $newsArticle->getAbout(),
            'publisher' => $newsArticle->getPublisher(),
            'url' => $newsArticle->getUrl()->toString(),
            'publisherLogo' => $newsArticle->getPublisherLogo()->toString(),
        ];

        return $data;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data === NewsArticle::class;
    }
}
