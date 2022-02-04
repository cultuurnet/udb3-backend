<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class RenameRole extends AbstractCommand
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
