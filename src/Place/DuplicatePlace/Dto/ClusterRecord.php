<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\DuplicatePlace\Dto;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ClusterRecord
{
    private string $clusterId;

    private UuidInterface $placeUuid;

    public function __construct(string $clusterId, UuidInterface $placeUuid)
    {
        $this->clusterId = $clusterId;
        $this->placeUuid = $placeUuid;
    }

    public function getClusterId(): string
    {
        return $this->clusterId;
    }

    public function getPlaceUuid(): UuidInterface
    {
        return $this->placeUuid;
    }

    public static function fromArray(array $array): self
    {
        return new self($array['cluster_id'], Uuid::fromString($array['place_uuid']));
    }
}
