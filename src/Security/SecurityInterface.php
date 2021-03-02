<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use ValueObjects\StringLiteral\StringLiteral;

interface SecurityInterface
{
    /**
     * @return boolean
     */
    public function allowsUpdateWithCdbXml(StringLiteral $offerId);

    /**
     * Returns if the event allows updates through the UDB3 core APIs.
     *
     * @return bool
     */
    public function isAuthorized(AuthorizableCommandInterface $command);
}
