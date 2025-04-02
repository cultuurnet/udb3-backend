<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

final class DeleteOwnership
{
    private Uuid $id;
    private string $userId;

    public function __construct(Uuid $id, string $userId)
    {
        $this->id = $id;
        $this->userId = $userId;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }
}
