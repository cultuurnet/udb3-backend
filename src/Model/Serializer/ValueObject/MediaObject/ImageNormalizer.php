<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject;

use Broadway\Repository\Repository;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Media\MediaObject;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\AiGeneratedImageDescription;
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

        $normalized = [
            '@id' => $this->mediaIriGenerator->iri($image->getId()->toString()),
            '@type' => 'schema:ImageObject',
            'id' => $image->getId()->toString(),
            'contentUrl' => $mediaObject->getSourceLocation()->toString(),
            'thumbnailUrl' => $mediaObject->getSourceLocation()->toString(),
            'description' => $image->getDescription()->toString(),
            'copyrightHolder' => $image->getCopyrightHolder()->toString(),
            'inLanguage' => $image->getLanguage()->toString(),
        ];

        if ($image->isAiGenerated() !== null) {
            $normalized['isAiGenerated'] = $image->isAiGenerated();
        }

        if ($image->isAiGenerated() === true) {
            $normalized['description'] = AiGeneratedImageDescription::addSuffix(
                $normalized['description'],
                $image->getLanguage()
            );
        }

        return $normalized;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data === Image::class;
    }
}
