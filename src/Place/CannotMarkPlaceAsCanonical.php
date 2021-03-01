<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use Exception;

final class CannotMarkPlaceAsCanonical extends Exception
{
    public static function becauseItIsDeleted(string $placeId): self
    {
        return new self('Cannot mark place ' . $placeId . ' as canonical because it is deleted');
    }

    public static function becauseItIsAlreadyADuplicate(string $placeId): self
    {
        return new self('Cannot mark place ' . $placeId . ' as canonical because it is a duplicate');
    }
}
