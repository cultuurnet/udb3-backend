<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\Events;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

final class MediaObjectCreated implements Serializable
{
    private Uuid $mediaObjectId;

    private MIMEType $mimeType;
    private Description $description;

    private CopyrightHolder $copyrightHolder;

    private Url $sourceLocation;

    private Language $language;

    public function __construct(
        Uuid $id,
        MIMEType $fileType,
        Description $description,
        CopyrightHolder $copyrightHolder,
        Url $sourceLocation,
        Language $language
    ) {
        $this->mediaObjectId = $id;
        $this->mimeType = $fileType;
        $this->description = $description;
        $this->copyrightHolder = $copyrightHolder;
        $this->sourceLocation = $sourceLocation;
        $this->language = $language;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function getMediaObjectId(): Uuid
    {
        return $this->mediaObjectId;
    }

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function getCopyrightHolder(): CopyrightHolder
    {
        return $this->copyrightHolder;
    }

    public function getMimeType(): MIMEType
    {
        return $this->mimeType;
    }

    public function getSourceLocation(): Url
    {
        return $this->sourceLocation;
    }

    public function serialize(): array
    {
        return [
            'media_object_id' => $this->getMediaObjectId()->toString(),
            'mime_type' => $this->getMimeType()->toString(),
            'description' => $this->getDescription()->toString(),
            'copyright_holder' => $this->getCopyrightHolder()->toString(),
            'source_location' => $this->getSourceLocation()->toString(),
            'language' => $this->getLanguage()->toString(),
        ];
    }

    public static function deserialize(array $data): MediaObjectCreated
    {
        // There are some older events that contain copyright_holder with less then 2 characters.
        // This is fixed here by adding an `_` instead of manually modifying the event store.
        $copyrightHolderData = $data['copyright_holder'];
        if (strlen($copyrightHolderData) < 2) {
            $copyrightHolderData .= '_';
        }
        // Some old events also exist with a copyright of more than 250 characters.
        // Those copyrights are truncated to 250 characters.
        if (strlen($copyrightHolderData) > 250) {
            $copyrightHolderData = substr($copyrightHolderData, 0, 250);
        }

        return new self(
            new Uuid($data['media_object_id']),
            new MIMEType($data['mime_type']),
            new Description($data['description']),
            new CopyrightHolder($copyrightHolderData),
            new Url($data['source_location']),
            array_key_exists('language', $data) ? new Language($data['language']) : new Language('nl')
        );
    }
}
