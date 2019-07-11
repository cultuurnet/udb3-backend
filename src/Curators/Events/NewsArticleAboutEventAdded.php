<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Curators\Events;

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

    public function __construct(string $newsArticleId, string $eventId)
    {
        $this->newsArticleId = $newsArticleId;
        $this->eventId = $eventId;
    }

    public function getNewsArticleId(): string
    {
        return $this->newsArticleId;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }
}
