<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventSourcing\DBAL;

class UniqueConstraintException extends \Exception
{
    private string $duplicateValue;

    public function __construct(string $uuid, string $duplicateValue)
    {
        parent::__construct('Not unique: uuid = ' . $uuid . ', duplicate value = ' . $duplicateValue);
        $this->duplicateValue = $duplicateValue;
    }

    public function getDuplicateValue(): string
    {
        return $this->duplicateValue;
    }
}
