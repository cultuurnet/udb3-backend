<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Exception;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

final class RoleNotFound extends \DomainException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function fromUuid(Uuid $uuid): self
    {
        return new self(sprintf('Role %s not found', $uuid->toString()));
    }
}
