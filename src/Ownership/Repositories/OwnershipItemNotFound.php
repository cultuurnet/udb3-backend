<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Repositories;

use Exception;

final class OwnershipItemNotFound extends Exception
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function byId(string $id): self
    {
        return new OwnershipItemNotFound('Ownership with id "' . $id . '" was not found.');
    }

    public static function byItemIdAndOwnerId(string $itemId, string $ownerId): self
    {
        return new OwnershipItemNotFound('Ownership with item id "' . $itemId . '" and owner id "' . $ownerId . '" was not found.');
    }
}
