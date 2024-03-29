<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User;

interface Auth0ManagementTokenRepository
{
    public function token(): ?Auth0Token;

    public function store(Auth0Token $token): void;
}
