<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\ManagementToken;

interface ManagementTokenGenerator
{
    public function newToken(): ManagementToken;
}
