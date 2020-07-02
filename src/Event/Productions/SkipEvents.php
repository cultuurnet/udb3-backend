<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

final class SkipEvents
{
    /**
     * @var string
     */
    private $eventIds;


    public function __construct(array $eventIds)
    {
        $this->eventIds = $eventIds;
    }

    public function getEventIds(): array
    {
        return $this->eventIds;
    }
}
