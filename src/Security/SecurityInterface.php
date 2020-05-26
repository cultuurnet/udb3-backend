<?php

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use ValueObjects\StringLiteral\StringLiteral;

interface SecurityInterface
{
    /**
     * @param StringLiteral $offerId
     * @return boolean
     */
    public function allowsUpdateWithCdbXml(StringLiteral $offerId);

    /**
     * Returns if the event allows updates through the UDB3 core APIs.
     *
     * @param AuthorizableCommandInterface $command
     * @return bool
     */
    public function isAuthorized(AuthorizableCommandInterface $command);
}
