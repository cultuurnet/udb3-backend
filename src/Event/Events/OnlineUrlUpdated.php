<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use Broadway\Serializer\Serializable;

final class OnlineUrlUpdated implements Serializable
{
    private string $eventId;

    private string $onlineUrl;

    public function __construct(string $eventId, string $onlineUrl)
    {
        $this->eventId = $eventId;
        $this->onlineUrl = $onlineUrl;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getOnlineUrl(): string
    {
        return $this->onlineUrl;
    }

    public static function deserialize(array $data): OnlineUrlUpdated
    {
        return new OnlineUrlUpdated(
            $data['eventId'],
            $data['onlineUrl']
        );
    }

    public function serialize(): array
    {
        return [
            'eventId' => $this->eventId,
            'onlineUrl' => $this->onlineUrl,
        ];
    }
}
