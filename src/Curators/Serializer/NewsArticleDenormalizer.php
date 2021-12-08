<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators\Serializer;

use CultuurNet\UDB3\Curators\NewsArticle;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class NewsArticleDenormalizer implements DenormalizerInterface
{
    private UUID $uuid;

    public function __construct(UUID $uuid)
    {
        $this->uuid = $uuid;
    }

    public function denormalize($data, $type, $format = null, array $context = []): NewsArticle
    {
        return new NewsArticle(
            $this->uuid,
            $data['headline'],
            new Language($data['inLanguage']),
            $data['text'],
            $data['about'],
            $data['publisher'],
            new Url($data['url']),
            new Url($data['publisherLogo'])
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === NewsArticle::class;
    }
}
