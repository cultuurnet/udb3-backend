<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Curators\Events;

use CultuurNet\UDB3\Silex\Curators\NewsArticleId;
use ValueObjects\StringLiteral\StringLiteral;

final class NewsArticleAboutEventAdded
{
    /**
     * @var NewsArticleId
     */
    private $newsArticleId;

    /**
     * @var StringLiteral
     */
    private $eventId;

    public function __construct(NewsArticleId $newsArticleId, StringLiteral $eventId)
    {
        $this->newsArticleId = $newsArticleId;
        $this->eventId = $eventId;
    }

    public function getNewsArticleId(): NewsArticleId
    {
        return $this->newsArticleId;
    }

    public function getEventId(): StringLiteral
    {
        return $this->eventId;
    }
}
