<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

final class Video
{
    public const REGEX = '/^http(s?):\/\/(www\.)?((youtube\.com\/watch\?v=([^\/#&?]*))|(vimeo\.com\/([^\/#&?]*))|(youtu\.be\/([^\/#&?]*)))/';

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

    public function getId(): string
    {
        return $this->id;
    }

    public function withUrl(Url $url): Video
    {
        $clone = clone $this;
        $clone->url = $url;
        return $clone;
    }

    public function getUrl(): Url
    {
        return $this->url;
    }

    public function withLanguage(Language $language): Video
    {
        $clone = clone $this;
        $clone->language = $language;
        return $clone;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function withCopyrightHolder(CopyrightHolder $copyright): Video
    {
        $clone = clone $this;
        $clone->copyrightHolder = $copyright;
        return $clone;
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

        if (!$this->url->sameAs($video->getUrl())) {
            return false;
        }

        if (!$this->language->sameAs($video->getLanguage())) {
            return false;
        }

        $copyrightHolder = $this->copyrightHolder ? $this->copyrightHolder->toString() : null;
        $otherCopyrightHolder = $video->getCopyrightHolder() ? $video->getCopyrightHolder()->toString() : null;

        return $copyrightHolder === $otherCopyrightHolder;
    }
}
