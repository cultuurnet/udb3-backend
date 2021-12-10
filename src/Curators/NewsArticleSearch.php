<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators;

use InvalidArgumentException;

final class NewsArticleSearch
{
    private ?string $publisher;

    private ?string $about;

    private ?string $url;

    private int $startPage = 1;
    private int $itemsPerPage = 30;

    public function __construct(?string $publisher, ?string $about, ?string $url)
    {
        $this->publisher = $publisher;
        $this->about = $about;
        $this->url = $url;
    }

    public function withStartPage(int $page): NewsArticleSearch
    {
        if ($page < 1) {
            throw new InvalidArgumentException('Page should start from 1');
        }

        $clone = clone $this;
        $clone->startPage = $page;
        return $clone;
    }

    public function withItemsPerPage(int $itemsPerPage): NewsArticleSearch
    {
        if ($itemsPerPage < 1) {
            throw new InvalidArgumentException('Items per page should be at least 1');
        }

        $clone = clone $this;
        $clone->itemsPerPage = $itemsPerPage;
        return $clone;
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

    public function getStartPage(): int
    {
        return $this->startPage;
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }
}
