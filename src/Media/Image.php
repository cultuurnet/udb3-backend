<?php

namespace CultuurNet\UDB3\Media;

use Broadway\Serializer\SerializableInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Media\Properties\CopyrightHolder;
use CultuurNet\UDB3\Media\Properties\Description;
use ValueObjects\Identity\UUID;
use ValueObjects\Web\Url;

final class Image implements SerializableInterface
{
    /**
     * @var UUID
     */
    protected $mediaObjectId;

    /**
     * @var MIMEType
     */
    protected $mimeType;

    /**
     * @var Description
     */
    protected $description;

    /**
     * @var CopyrightHolder
     */
    protected $copyrightHolder;

    /**
     * @var Url
     */
    protected $sourceLocation;

    /**
     * @var Language
     */
    protected $language;

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
        return new self(
            new UUID($data['media_object_id']),
            new MIMEType($data['mime_type']),
            new Description($data['description']),
            new CopyrightHolder($data['copyright_holder']),
            Url::fromNative($data['source_location']),
            array_key_exists('language', $data) ? new Language($data['language']) : new Language('nl')
        );
    }

    public function serialize(): array
    {
        return [
            'media_object_id' => (string) $this->getMediaObjectId(),
            'mime_type' => (string) $this->getMimeType(),
            'description' => (string) $this->getDescription(),
            'copyright_holder' => (string) $this->getCopyrightHolder(),
            'source_location' => (string) $this->getSourceLocation(),
            'language' => (string) $this->getLanguage(),
        ];
    }
}
