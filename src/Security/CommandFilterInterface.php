<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

interface CommandFilterInterface
{
    /**
     * @return bool
     */
    public function matches(AuthorizableCommandInterface $command);
}
