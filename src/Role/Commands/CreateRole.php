<?php

namespace CultuurNet\UDB3\Role\Commands;

use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class CreateRole extends AbstractCommand
{
    /**
     * @var StringLiteral
     */
    private $name;

    public function __construct(
        UUID $uuid,
        StringLiteral $name
    ) {
        parent::__construct($uuid);

        $this->name = $name;
    }

    /**
     * @return StringLiteral
     */
    public function getName()
    {
        return $this->name;
    }
}
