<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User;

interface Auth0ManagementTokenRepository
{
    public function token(): ?string;

    public function store(string $token): void;
}
