<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators\Events;

use CultuurNet\UDB3\Curators\Publisher;

final class NewsArticleAboutEventAdded
{
    /**
     * @var string
     */
    private $newsArticleId;

    /**
     * @var string
     */
    private $eventId;

    /**
     * @var Publisher
     */
    private $publisher;

    public function __construct(string $newsArticleId, string $eventId, Publisher $publisher)
    {
        $this->newsArticleId = $newsArticleId;
        $this->eventId = $eventId;
        $this->publisher = $publisher;
    }

    public function getNewsArticleId(): string
    {
        return $this->newsArticleId;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getPublisher(): Publisher
    {
        return $this->publisher;
    }
}
