<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\Command;

abstract class SavedSearchCommand
{
    protected string $userId;

    public function __construct(
        string $userId
    ) {
        $this->userId = $userId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }
}
