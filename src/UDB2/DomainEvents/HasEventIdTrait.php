<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\DomainEvents;

use ValueObjects\StringLiteral\StringLiteral;

trait HasEventIdTrait
{
    /**
     * @var StringLiteral
     */
    protected $eventId;

    private function setEventId(StringLiteral $eventId)
    {
        if ($eventId->isEmpty()) {
            throw new \InvalidArgumentException('event id can not be empty');
        }
        $this->eventId = $eventId;
    }

    /**
     * @return StringLiteral
     */
    public function getEventId()
    {
        return $this->eventId;
    }

    /**
     * @return array
     */
    public function serialize()
    {
        return [
            'eventId' => (string) $this->getEventId(),
        ];
    }
}
