<?php

namespace CultuurNet\UDB3\Place;

use Exception;

class CannotMarkPlaceAsCanonical extends Exception
{
    public static function becauseItIsDeleted(string $placeId): self
    {
        return new static('Cannot mark place ' . $placeId . ' as canonical because it is deleted');
    }

    public static function becauseItIsAlreadyADuplicate(string $placeId): self
    {
        return new static('Cannot mark place ' . $placeId . ' as canonical because it is a duplicate');
    }
}
