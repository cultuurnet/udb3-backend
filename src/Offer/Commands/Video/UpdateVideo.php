<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Video;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

final class UpdateVideo
{
    private string $id;

    private ?Url $url = null;

    private ?Language $language = null;

    private ?CopyrightHolder $copyrightHolder = null;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUrl(): ?Url
    {
        return $this->url;
    }

    public function withUrl(Url $url): UpdateVideo
    {
        $clone = clone $this;
        $clone->url = $url;
        return $clone;
    }

    public function getLanguage(): ?Language
    {
        return $this->language;
    }

    public function withLanguage(Language $language): UpdateVideo
    {
        $clone = clone $this;
        $clone->language = $language;
        return $clone;
    }

    public function getCopyrightHolder(): ?CopyrightHolder
    {
        return $this->copyrightHolder;
    }

    public function withCopyrightHolder(CopyrightHolder $copyright): UpdateVideo
    {
        $clone = clone $this;
        $clone->copyrightHolder = $copyright;
        return $clone;
    }
}
