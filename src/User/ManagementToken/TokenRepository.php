<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\ManagementToken;

interface TokenRepository
{
    public function token(): ?ManagementToken;

    public function store(ManagementToken $token): void;
}
