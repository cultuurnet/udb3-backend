<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\ValueObjects\Query;

class AddConstraint extends AbstractCommand
{
    /**
     * @var Query
     */
    private $query;

    public function __construct(
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
}
