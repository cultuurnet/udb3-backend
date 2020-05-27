<?php

namespace CultuurNet\UDB3\Security;

use ValueObjects\StringLiteral\StringLiteral;

interface UserIdentificationInterface
{
    /**
     * @return bool
     */
    public function isGodUser();

    /**
     * @return StringLiteral|null
     */
    public function getId();
}
