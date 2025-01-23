<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\MediaObject;

use CultuurNet\UDB3\Media\Image as MediaImage;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Media\MediaObjectNotFoundException;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Image;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Images;

class MediaManagerImageCollectionFactory implements ImageCollectionFactory
{
    private MediaManagerInterface $mediaManager;

    public function __construct(MediaManagerInterface $mediaManager)
    {
        $this->mediaManager = $mediaManager;
    }

    public function fromImages(Images $images): ImageCollection
    {
        $imageCollection = array_map(
            function (Image $image) {
                $id = $image->getId();

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

                return new MediaImage(
                    $id,
                    $mediaObjectAggregate->getMimeType(),
                    new Description($image->getDescription()->toString()),
                    $image->getCopyrightHolder(),
                    $mediaObjectAggregate->getSourceLocation(),
                    $image->getLanguage()
                );
            },
            $images->toArray()
        );

        $imageCollection = array_filter($imageCollection);

        return ImageCollection::fromArray($imageCollection);
    }
}
