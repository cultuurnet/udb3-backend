<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UserId;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

final class ApproveOwnership
{
    private UUID $id;
    private UserId $requesterId;

    public function __construct(UUID $id, UserId $requesterId)
    {
        $this->id = $id;
        $this->requesterId = $requesterId;
    }

    public function getId(): UUID
    {
        return $this->id;
    }

    public function getRequesterId(): UserId
    {
        return $this->requesterId;
    }
}
