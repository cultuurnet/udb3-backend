<?php

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication\Token;

use CultuurNet\UDB3\User\UserIdentityDetails;

interface Token
{
    public function getUserId(): string;
    public function getUserIdentityDetails(): ?UserIdentityDetails;
}
