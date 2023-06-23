<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Actor;

use Broadway\Serializer\Serializable;

abstract class ActorEvent implements Serializable
{
    protected string $actorId;

    public function __construct(string $actorId)
    {
        $this->actorId = $actorId;
    }

    public function getActorId(): string
    {
        return $this->actorId;
    }

    public function serialize(): array
    {
        return [
            'actor_id' => $this->actorId,
        ];
    }
}
