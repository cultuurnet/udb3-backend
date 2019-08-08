<?php

namespace CultuurNet\UDB3\Symfony\Management\User;

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
