<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Media\MediaObject;
use CultuurNet\UDB3\Media\MediaObjectRepository;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObject as MediaObjectDto;

class ImagesToMediaObjectReferencesConvertor
{
    private MediaObjectRepository $mediaObjectRepository;

    public function __construct(MediaObjectRepository $mediaObjectRepository)
    {
        $this->mediaObjectRepository = $mediaObjectRepository;
    }

    public function convert(Images $images): MediaObjectReferences
    {
        return new MediaObjectReferences(... array_map(function (Image $image) {
            $mediaObject = $this->mediaObjectRepository->load($image->getId()->toString());

            if (!$mediaObject instanceof MediaObject) {
                return null;
            }

            $mediaObjectDto = new MediaObjectDto(
                $mediaObject->getMediaObjectId(),
                MediaObjectType::imageObject(),
                $mediaObject->getSourceLocation(),
                $mediaObject->getSourceLocation(),
            );

            return MediaObjectReference::createWithEmbeddedMediaObject(
                $mediaObjectDto,
                $image->getDescription(),
                $image->getCopyrightHolder(),
                $image->getLanguage()
            );
        }, $images->toArray()));
    }
}
