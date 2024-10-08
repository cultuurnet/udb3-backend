<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\MediaObject;

use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Media\MediaObjectNotFoundException;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectReference;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectReferences;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectType;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

class MediaManagerImageCollectionFactory implements ImageCollectionFactory
{
    private MediaManagerInterface $mediaManager;

    public function __construct(MediaManagerInterface $mediaManager)
    {
        $this->mediaManager = $mediaManager;
    }

    public function fromMediaObjectReferences(MediaObjectReferences $mediaObjectReferences): ImageCollection
    {
        $mediaObjectsReferences = $mediaObjectReferences->filter(
            function (MediaObjectReference $mediaObjectReference) {
                $embeddedMediaObject = $mediaObjectReference->getEmbeddedMediaObject();

                return is_null($embeddedMediaObject) ||
                    $embeddedMediaObject->getType()->sameAs(MediaObjectType::imageObject());
            }
        );

        $images = array_map(
            function (MediaObjectReference $mediaObjectReference) {
                $id = $mediaObjectReference->getMediaObjectId();

                try {
                    $mediaObjectAggregate = $this->mediaManager->get($id);
                } catch (MediaObjectNotFoundException $e) {
                    return null;
                }

                $allowedMimeTypes = [
                    MIMEType::fromSubtype('jpeg'),
                    MIMEType::fromSubtype('png'),
                    MIMEType::fromSubtype('gif'),
                ];
                $mimeType = $mediaObjectAggregate->getMimeType();

                if (!in_array($mimeType, $allowedMimeTypes)) {
                    return null;
                }

                return new Image(
                    $mediaObjectAggregate->getMediaObjectId(),
                    $mediaObjectAggregate->getMimeType(),
                    new Description($mediaObjectReference->getDescription()->toString()),
                    $mediaObjectReference->getCopyrightHolder(),
                    $mediaObjectAggregate->getSourceLocation(),
                    new Language($mediaObjectReference->getLanguage()->toString())
                );
            },
            $mediaObjectsReferences->toArray()
        );

        $images = array_filter($images);

        return ImageCollection::fromArray($images);
    }
}
