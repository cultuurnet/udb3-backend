<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\ValueObjects\Query;

class AddConstraint extends AbstractCommand
{
    private Query $query;

    public function __construct(
        Uuid $uuid,
        Query $query
    ) {
        parent::__construct($uuid);
        $this->query = $query;
    }

    public function getQuery(): Query
    {
        return $this->query;
    }
}
