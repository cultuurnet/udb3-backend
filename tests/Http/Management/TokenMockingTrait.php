<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Management;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebToken;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebTokenFactory;

trait TokenMockingTrait
{
    private function createMockToken(string $userId): JsonWebToken
    {
        return JsonWebTokenFactory::createWithClaims(['uid' => $userId]);
    }
}
