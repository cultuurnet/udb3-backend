<?php

namespace CultuurNet\UDB3;

use Broadway\Serializer\SerializableInterface;

final class MediaObject implements SerializableInterface, JsonLdSerializableInterface
{
    /**
     * @var string|null
     */
    protected $type;

    /**
     * @var string
     */
    protected $internalId;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $thumbnailUrl;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $copyrightHolder;

    public function __construct(string $url, string $thumbnailUrl, string $description, string $copyrightHolder, string $internalId = '', ?string $type = null)
    {
        $this->type = $type;
        $this->url = $url;
        $this->thumbnailUrl = $thumbnailUrl;
        $this->description = $description;
        $this->copyrightHolder = $copyrightHolder;
        $this->internalId = $internalId;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getThumbnailUrl(): string
    {
        return $this->thumbnailUrl;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCopyrightHolder(): string
    {
        return $this->copyrightHolder;
    }

    public function getInternalId(): string
    {
        return $this->internalId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public static function deserialize(array $data): MediaObject
    {
        $type = !empty($data['type']) ? $data['type'] : null;
        return new self($data['url'], $data['thumbnail_url'], $data['description'], $data['copyright_holder'], $data['internal_id'], $type);
    }

    public function serialize(): array
    {
        return [
            'type' => $this->type,
            'url' => $this->url,
            'thumbnail_url' => $this->thumbnailUrl,
            'description' => $this->description,
            'copyright_holder' => $this->copyrightHolder,
            'internal_id' => $this->internalId,
        ];
    }

    public function toJsonLd(): array
    {
        $jsonLd = [];
        if (!empty($this->type)) {
            $jsonLd['@type'] = $this->type;
        }

        $jsonLd['url'] = $this->url;
        $jsonLd['thumbnailUrl'] = $this->thumbnailUrl;
        $jsonLd['description'] = $this->description;
        $jsonLd['copyrightHolder'] = $this->copyrightHolder;

        return $jsonLd;
    }
}
