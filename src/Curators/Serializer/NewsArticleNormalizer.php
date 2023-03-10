<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators\Serializer;

use CultuurNet\UDB3\Curators\NewsArticle;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class NewsArticleNormalizer implements NormalizerInterface
{
    private bool $jsonLd;

    public function __construct(bool $jsonLd = false)
    {
        $this->jsonLd = $jsonLd;
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

        if ($newsArticle->getImage() !== null) {
            $data += [
                'image' => [
                    'url' => $newsArticle->getImage()->getImageUrl()->toString(),
                    'copyrightHolder' => $newsArticle->getImage()->getCopyrightHolder()->toString(),
                ],
            ];
        }

        return $data;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data === NewsArticle::class;
    }
}
