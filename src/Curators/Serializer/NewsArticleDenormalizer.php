<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators\Serializer;

use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Curators\NewsArticle;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class NewsArticleDenormalizer implements DenormalizerInterface
{
    private UuidGeneratorInterface $uuidGenerator;

    public function __construct(UuidGeneratorInterface $uuidGenerator)
    {
        $this->uuidGenerator = $uuidGenerator;
    }

    public function denormalize($data, $type, $format = null, array $context = []): NewsArticle
    {
        if (!isset($data['id'])) {
            $data['id'] = $this->uuidGenerator->generate();
        }

        return new NewsArticle(
            new UUID($data['id']),
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
