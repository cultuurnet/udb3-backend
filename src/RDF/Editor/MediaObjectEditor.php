<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\Editor;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectReference;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectReferences;
use DomainException;
use EasyRdf\Literal;
use EasyRdf\Resource;

final class MediaObjectEditor
{
    private const TYPE_MEDIA_OBJECT = 'schema:ImageObject';
    private const PROPERTY_MEDIA = 'schema:image';

    private const TYPE_URL = 'schema:URL';

    private const PROPERTY_IDENTIFIER = 'schema:identifier';
    private const PROPERTY_URL = 'schema:url';
    private const PROPERTY_COPYRIGHT_HOLDER = 'schema:copyrightHolder';
    private const PROPERTY_DESCRIPTION = 'schema:description';
    private const PROPERTY_IN_LANGUAGE = 'schema:inLanguage';

    public function setImages(Resource $resource, MediaObjectReferences $mediaObjectReferences): void
    {
        foreach ($mediaObjectReferences as $mediaObjectReference) {
            try {
                $mediaResource = $this->createMediaResource($resource, $mediaObjectReference);
                $resource->add(self::PROPERTY_MEDIA, $mediaResource);
            } catch (DomainException $e) {
                // We cannot add media resources without embedded media object
            }
        }
    }

    private function createMediaResource(Resource $resource, MediaObjectReference $mediaObjectReference): Resource
    {
        if ($mediaObjectReference->getEmbeddedMediaObject() === null) {
            throw new DomainException('We cannot add media resources without embedded media object');
        }

        $mediaResource = $resource->getGraph()->newBNode([self::TYPE_MEDIA_OBJECT]);

        $mediaResource->set(
            self::PROPERTY_IDENTIFIER,
            new Literal($mediaObjectReference->getMediaObjectId()->toString())
        );
        $mediaResource->set(
            self::PROPERTY_URL,
            new Literal($mediaObjectReference->getEmbeddedMediaObject()->getContentUrl()->toString(), null, self::TYPE_URL)
        );
        $mediaResource->set(
            self::PROPERTY_COPYRIGHT_HOLDER,
            new Literal($mediaObjectReference->getCopyrightHolder()->toString())
        );
        $mediaResource->set(
            self::PROPERTY_DESCRIPTION,
            new Literal($mediaObjectReference->getDescription()->toString())
        );
        $mediaResource->set(
            self::PROPERTY_IN_LANGUAGE,
            new Literal($mediaObjectReference->getLanguage()->toString())
        );

        return $mediaResource;
    }
}
