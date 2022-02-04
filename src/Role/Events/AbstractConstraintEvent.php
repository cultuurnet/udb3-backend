<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\ValueObjects\Query;

abstract class AbstractConstraintEvent extends AbstractEvent
{
    private Query $query;

    final public function __construct(
        UUID $uuid,
        Query $query
    ) {
        parent::__construct($uuid);
        $this->query = $query;
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    public static function deserialize(array $data): AbstractConstraintEvent
    {
        return new static(
            new UUID($data['uuid']),
            new Query($data['query'])
        );
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'query' => $this->query->toNative(),
        ];
    }
}
