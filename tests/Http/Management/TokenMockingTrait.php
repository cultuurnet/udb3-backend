<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Management;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\AbstractToken;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\JwtProviderV1Token;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\MockTokenStringFactory;

trait TokenMockingTrait
{
    private function createMockToken(string $userId): AbstractToken
    {
        return (
            new JwtProviderV1Token(
                MockTokenStringFactory::createWithClaims(
                    [
                        'uid' => $userId,
                        'nick' => 'nick',
                        'email' => 'mock@example.com',
                    ]
                )
            )
        )->authenticate();
    }
}
