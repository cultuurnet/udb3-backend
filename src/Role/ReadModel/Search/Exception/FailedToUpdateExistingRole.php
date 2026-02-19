<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Search\Exception;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use DomainException;

class FailedToUpdateExistingRole extends DomainException
{
    public static function fromUniqueConstraintViolationException(UniqueConstraintViolationException $e): self
    {
        return new self('UniqueConstraintViolationException occurred while saving role: ' . $e->getMessage(), 0, $e);
    }
}
