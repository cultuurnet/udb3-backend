<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

final class Image
{
    private UUID $id;

    private Language $language;

    private Description $description;

    private CopyrightHolder $copyrightHolder;

    public function __construct(
        UUID $id,
        Language $language,
        Description $description,
        CopyrightHolder $copyrightHolder
    ) {
        $this->id = $id;
        $this->language = $language;
        $this->description = $description;
        $this->copyrightHolder = $copyrightHolder;
    }

    public function getId(): UUID
    {
        return $this->id;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function getCopyrightHolder(): CopyrightHolder
    {
        return $this->copyrightHolder;
    }

    public function sameAs(Image $image): bool
    {
        if (!$this->id->sameAs($image->getId())) {
            return false;
        }

        if (!$this->language->sameAs($image->getLanguage())) {
            return false;
        }

        if (!$this->description->sameAs($image->getDescription())) {
            return false;
        }

        if (!$this->copyrightHolder->sameAs($image->getCopyrightHolder())) {
            return false;
        }

        return true;
    }
}
