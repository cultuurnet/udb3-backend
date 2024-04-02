<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

final class RejectOwnership
{
    private UUID $id;

    public function __construct(UUID $id)
    {
        $this->id = $id;
    }

    public function getId(): UUID
    {
        return $this->id;
    }
}
