<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\Editor;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\Image;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Images;
use DomainException;
use EasyRdf\Literal;
use EasyRdf\Resource;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ImageEditor
{
    private const TYPE_MEDIA_OBJECT = 'schema:ImageObject';
    private const PROPERTY_MEDIA = 'schema:image';
    private const TYPE_URL = 'schema:URL';
    private const PROPERTY_IDENTIFIER = 'schema:identifier';
    private const PROPERTY_URL = 'schema:url';
    private const PROPERTY_COPYRIGHT_HOLDER = 'schema:copyrightHolder';
    private const PROPERTY_DESCRIPTION = 'schema:description';
    private const PROPERTY_IN_LANGUAGE = 'schema:inLanguage';
    private NormalizerInterface $imageNormalizer;

    public function __construct(NormalizerInterface $imageNormalizer)
    {
        $this->imageNormalizer = $imageNormalizer;
    }

    public function setImages(Resource $resource, Images $images): void
    {
        foreach ($images as $image) {
            try {
                $resource->add(self::PROPERTY_MEDIA, $this->createImage($resource, $image));
            } catch (DomainException $e) {
                // We cannot add media resources without embedded media object
            }
        }
    }

    private function createImage(Resource $resource, Image $image): Resource
    {
        $normalizedImage = $this->imageNormalizer->normalize($image);
        $mediaResource = $resource->getGraph()->newBNode([self::TYPE_MEDIA_OBJECT]);

        $mediaResource->set(
            self::PROPERTY_IDENTIFIER,
            new Literal($image->getId()->toString())
        );
        $mediaResource->set(
            self::PROPERTY_URL,
            new Literal($normalizedImage['contentUrl'], null, self::TYPE_URL)
        );
        $mediaResource->set(
            self::PROPERTY_COPYRIGHT_HOLDER,
            new Literal($image->getCopyrightHolder()->toString())
        );
        $mediaResource->set(
            self::PROPERTY_DESCRIPTION,
            new Literal($image->getDescription()->toString())
        );
        $mediaResource->set(
            self::PROPERTY_IN_LANGUAGE,
            new Literal($image->getLanguage()->toString())
        );

        return $mediaResource;
    }
}
