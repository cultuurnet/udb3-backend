<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

final class Video
{
    public const REGEX = '/^http(s?):\/\/(www\.)?((youtube\.com\/watch\?v=([^\/#&?]*))|(vimeo\.com\/([^\/#&?]*)))/';

    private string $id;

    private Url $url;

    private Language $language;

    private ?CopyrightHolder $copyrightHolder = null;

    public function __construct(
        string $id,
        Url $url,
        Language $language
    ) {
        $this->id = $id;
        $this->url = $url;
        $this->language = $language;
    }

    public function withCopyrightHolder(CopyrightHolder $copyright): Video
    {
        $clone = clone $this;
        $clone->copyrightHolder = $copyright;
        return $clone;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUrl(): Url
    {
        return $this->url;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function getCopyrightHolder(): ?CopyrightHolder
    {
        return $this->copyrightHolder;
    }

    public function sameAs(Video $video): bool
    {
        if ($this->id !== $video->getId()) {
            return false;
        }

        if (!$this->url->sameAs($video->url)) {
            return false;
        }

        if (!$this->language->sameAs($video->language)) {
            return false;
        }

        if ($this->copyrightHolder === null && $video->getCopyrightHolder() !== null) {
            return false;
        }

        if ($this->copyrightHolder !== null && $video->getCopyrightHolder() === null) {
            return false;
        }

        if (!$this->copyrightHolder->sameAs($video->getCopyrightHolder())) {
            return false;
        }

        return true;
    }
}
