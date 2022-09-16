<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\StringLiteral;

class RenameRole extends AbstractCommand
{
    private string $name;

    public function __construct(
        UUID $uuid,
        string $name
    ) {
        parent::__construct($uuid);

        $this->name = $name;
    }

    public function getName(): StringLiteral
    {
        return new StringLiteral($this->name);
    }
}
