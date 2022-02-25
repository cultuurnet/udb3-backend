<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

use CultuurNet\UDB3\StringLiteral;

class EventExportQuery extends StringLiteral
{
    public function __construct(string $value)
    {
        parent::__construct(trim($value));

        if ($this->isEmpty()) {
            throw new \InvalidArgumentException('Query can not be empty');
        }
    }
}
