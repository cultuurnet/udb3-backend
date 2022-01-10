<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators\Serializer;

use CultuurNet\UDB3\Curators\NewsArticle;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class NewsArticleNormalizer implements NormalizerInterface
{
    /**
     * @param NewsArticle $newsArticle
     */
    public function normalize($newsArticle, $format = null, array $context = []): array
    {
        return [
            '@context' => '/contexts/NewsArticle',
            '@id' => '/news-articles/' . $newsArticle->getId()->toString(),
            '@type' => 'https://schema.org/NewsArticle',
            'id' => $newsArticle->getId()->toString(),
            'headline' => $newsArticle->getHeadline(),
            'inLanguage' => $newsArticle->getLanguage()->toString(),
            'text' => $newsArticle->getText(),
            'about' => $newsArticle->getAbout(),
            'publisher' => $newsArticle->getPublisher(),
            'url' => $newsArticle->getUrl()->toString(),
            'publisherLogo' => $newsArticle->getPublisherLogo()->toString(),
        ];
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data === NewsArticle::class;
    }
}
