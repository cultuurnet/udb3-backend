<?php

namespace CultuurNet\UDB3\Place;

use Exception;

final class CannotMarkPlaceAsDuplicate extends Exception
{
    public static function becauseItIsDeleted(string $placeId): self
    {
        return new self('Cannot mark place ' . $placeId . ' as duplicate because it is deleted');
    }

    public static function becauseItIsAlreadyADuplicate(string $placeId): self
    {
        return new self('Cannot mark place ' . $placeId . ' as duplicate because it is already a duplicate');
    }
}
