<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators;

final class NewsArticleSearch
{
    private ?string $publisher;

    private ?string $about;

    private ?string $url;

    public function __construct(?string $publisher, ?string $about, ?string $url)
    {
        $this->publisher = $publisher;
        $this->about = $about;
        $this->url = $url;
    }

    public function getPublisher(): ?string
    {
        return $this->publisher;
    }

    public function getAbout(): ?string
    {
        return $this->about;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }
}
