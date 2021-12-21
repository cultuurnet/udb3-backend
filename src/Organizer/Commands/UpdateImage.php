<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

final class UpdateImage
{
    private string $organizerId;

    private UUID $imageId;

    private ?Language $language = null;

    private ?Description $description = null;

    private ?CopyrightHolder $copyrightHolder = null;

    public function __construct(string $organizerId, UUID $imageId)
    {
        $this->organizerId = $organizerId;
        $this->imageId = $imageId;
    }

    public function getOrganizerId(): string
    {
        return $this->organizerId;
    }

    public function getImageId(): UUID
    {
        return $this->imageId;
    }

    public function getLanguage(): ?Language
    {
        return $this->language;
    }

    public function withLanguage(Language $language): UpdateImage
    {
        $clone = clone $this;
        $clone->language = $language;
        return $clone;
    }

    public function getDescription(): ?Description
    {
        return $this->description;
    }

    public function withDescription(Description $description): UpdateImage
    {
        $clone = clone $this;
        $clone->description = $description;
        return $clone;
    }

    public function getCopyrightHolder(): ?CopyrightHolder
    {
        return $this->copyrightHolder;
    }

    public function withCopyrightHolder(CopyrightHolder $copyrightHolder): UpdateImage
    {
        $clone = clone $this;
        $clone->copyrightHolder = $copyrightHolder;
        return $clone;
    }
}
