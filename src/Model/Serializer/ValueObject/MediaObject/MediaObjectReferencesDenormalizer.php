<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\UuidParser;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectIDParser;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectReference;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectReferences;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class MediaObjectReferencesDenormalizer implements DenormalizerInterface
{
    private UuidParser $mediaObjectIdParser;

    public function __construct(UuidParser $mediaObjectIdParser = null)
    {
        if (!$mediaObjectIdParser) {
            $mediaObjectIdParser = new MediaObjectIDParser();
        }

        $this->mediaObjectIdParser = $mediaObjectIdParser;
    }

    /**
     * @inheritdoc
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new UnsupportedException("MediaObjectReferencesDenormalizer does not support {$class}.");
        }

        if (!is_array($data)) {
            throw new UnsupportedException('MediaObjects data should be an array.');
        }

        $references = array_map([$this, 'denormalizeMediaObjectReference'], $data);
        return new MediaObjectReferences(...$references);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === MediaObjectReferences::class;
    }

    /**
     * @todo Extract to a separate MediaObjectReferenceDenormalizer
     */
    private function denormalizeMediaObjectReference(array $referenceData): MediaObjectReference
    {
        $id = $this->mediaObjectIdParser->fromUrl(new Url($referenceData['@id']));
        $description = new Description($referenceData['description']);
        $copyrightHolder = new CopyrightHolder($referenceData['copyrightHolder']);
        $language = new Language($referenceData['inLanguage']);

        return MediaObjectReference::createWithMediaObjectId(
            $id,
            $description,
            $copyrightHolder,
            $language
        );
    }
}
