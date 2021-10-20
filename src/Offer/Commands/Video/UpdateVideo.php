<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Video;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;

final class UpdateVideo implements AuthorizableCommand
{
    private string $offerId;

    private string $videoId;

    private ?Url $url = null;

    private ?Language $language = null;

    private ?CopyrightHolder $copyrightHolder = null;

    public function __construct(string $offerId, string $videoId)
    {
        $this->offerId = $offerId;
        $this->videoId = $videoId;
    }

    public function getVideoId(): string
    {
        return $this->videoId;
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

    public function getItemId(): string
    {
        return $this->offerId;
    }

    public function getPermission(): Permission
    {
        return Permission::AANBOD_BEWERKEN();
    }
}
