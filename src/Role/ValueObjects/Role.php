<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ValueObjects;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

final class Role
{
    private Uuid $uuid;

    private string $name;

    private ?Query $constraintQuery;

    public function __construct(Uuid $uuid, string $name, ?Query $constraintQuery)
    {
        $this->uuid = $uuid;
        $this->name = $name;
        $this->constraintQuery = $constraintQuery;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getConstraintQuery(): ?Query
    {
        return $this->constraintQuery;
    }
}
