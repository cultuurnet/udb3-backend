<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Management\User;

use ValueObjects\StringLiteral\StringLiteral;

interface UserIdentificationInterface
{
    /**
     * @return bool
     */
    public function isGodUser();

    /**
     * @return StringLiteral
     */
    public function getId();
}
