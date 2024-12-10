<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

class CreateRole extends AbstractCommand
{
    private string $name;

    public function __construct(
        Uuid $uuid,
        string $name
    ) {
        parent::__construct($uuid);

        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
