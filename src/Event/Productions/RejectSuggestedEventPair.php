<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

final class RejectSuggestedEventPair
{
    /**
     * @var SimilarEventPair
     */
    private $eventPair;

    public function __construct(SimilarEventPair $eventPair)
    {
        $this->eventPair = $eventPair;
    }

    public function getEventIds(): array
    {
        return [
            $this->eventPair->getEventOne(),
            $this->eventPair->getEventTwo(),
        ];
    }
}
