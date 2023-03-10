<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

final class NewsArticle
{
    private UUID $id;

    private string $headline;

    private Language $language;

    private string $text;

    private string $about;

    private string $publisher;

    private Url $url;

    private Url $publisherLogo;

    private ?NewsArticleImage $image = null;

    public function __construct(
        UUID $id,
        string $headline,
        Language $language,
        string $text,
        string $about,
        string $publisher,
        Url $url,
        Url $publisherLogo
    ) {
        $this->id = $id;
        $this->headline = $headline;
        $this->language = $language;
        $this->text = $text;
        $this->about = $about;
        $this->publisher = $publisher;
        $this->url = $url;
        $this->publisherLogo = $publisherLogo;
    }

    public function getId(): UUID
    {
        return $this->id;
    }

    public function getHeadline(): string
    {
        return $this->headline;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getAbout(): string
    {
        return $this->about;
    }

    public function getPublisher(): string
    {
        return $this->publisher;
    }

    public function getUrl(): Url
    {
        return $this->url;
    }

    public function getPublisherLogo(): Url
    {
        return $this->publisherLogo;
    }

    public function withImage(NewsArticleImage $image): NewsArticle
    {
        $clone = clone $this;
        $clone->image = $image;
        return $clone;
    }

    public function getImage(): ?NewsArticleImage
    {
        return $this->image;
    }
}
