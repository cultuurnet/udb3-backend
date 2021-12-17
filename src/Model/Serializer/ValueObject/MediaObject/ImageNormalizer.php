<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject;

use Broadway\Repository\Repository;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Media\MediaObject;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Image;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ImageNormalizer implements NormalizerInterface
{
    private Repository $mediaRepository;

    private IriGeneratorInterface $mediaIriGenerator;

    public function __construct(Repository $mediaRepository, IriGeneratorInterface $mediaIriGenerator)
    {
        $this->mediaRepository = $mediaRepository;
        $this->mediaIriGenerator = $mediaIriGenerator;
    }

    /**
     * @param Image $image
     */
    public function normalize($image, $format = null, array $context = []): array
    {
        /** @var MediaObject $mediaObject */
        $mediaObject = $this->mediaRepository->load($image->getId()->toString());

        return [
            '@id' => $this->mediaIriGenerator->iri($image->getId()->toString()),
            'contentUrl' => (string) $mediaObject->getSourceLocation(),
            'thumbnailUrl' => (string) $mediaObject->getSourceLocation(),
            'language' => $image->getLanguage()->toString(),
            'description' => $image->getDescription()->toString(),
            'copyrightHolder' => $image->getCopyrightHolder()->toString(),
        ];
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data === Image::class;
    }
}
