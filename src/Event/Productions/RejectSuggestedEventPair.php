<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\Security\AuthorizableCommand;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

final class RejectSuggestedEventPair implements AuthorizableCommand
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

    public function getItemId(): string
    {
        return  $this->eventPair->getEventOne();
    }

    public function getEventPair(): SimilarEventPair
    {
        return $this->eventPair;
    }

    public function getPermission(): Permission
    {
        return Permission::PRODUCTIES_AANMAKEN();
    }
}
