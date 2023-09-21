<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

final class Image implements Serializable
{
    private UUID $mediaObjectId;

    private MIMEType $mimeType;

    private Description $description;

    private CopyrightHolder $copyrightHolder;

    private Url $sourceLocation;

    private Language $language;

    public function __construct(
        UUID $id,
        MIMEType $mimeType,
        Description $description,
        CopyrightHolder $copyrightHolder,
        Url $sourceLocation,
        Language $language
    ) {
        $this->mediaObjectId = $id;
        $this->mimeType = $mimeType;
        $this->description = $description;
        $this->copyrightHolder = $copyrightHolder;
        $this->sourceLocation = $sourceLocation;
        $this->language = $language;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function getMediaObjectId(): UUID
    {
        return $this->mediaObjectId;
    }

    public function getMimeType(): MIMEType
    {
        return $this->mimeType;
    }

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function getCopyrightHolder(): CopyrightHolder
    {
        return $this->copyrightHolder;
    }

    public function getSourceLocation(): Url
    {
        return $this->sourceLocation;
    }

    public static function deserialize(array $data): Image
    {
        // There are some older events that contain copyright_holder with less then 2 characters.
        // This is fixed here by adding an `_` instead of manually modifying the event store.
        $copyrightHolderData = $data['copyright_holder'];
        if (strlen($copyrightHolderData) < 2) {
            $copyrightHolderData .= '_';
        }

        return new self(
            new UUID($data['media_object_id']),
            new MIMEType($data['mime_type']),
            new Description($data['description']),
            new CopyrightHolder($copyrightHolderData),
            new Url($data['source_location']),
            array_key_exists('language', $data) ? new Language($data['language']) : new Language('nl')
        );
    }

    public function serialize(): array
    {
        return [
            'media_object_id' => $this->getMediaObjectId()->toString(),
            'mime_type' => $this->getMimeType()->toNative(),
            'description' => $this->getDescription()->toNative(),
            'copyright_holder' => $this->getCopyrightHolder()->toString(),
            'source_location' => $this->getSourceLocation()->toString(),
            'language' => (string) $this->getLanguage(),
        ];
    }
}
