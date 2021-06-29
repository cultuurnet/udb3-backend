<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Management;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebToken;
use Lcobucci\JWT\Claim\Basic as BasicClaim;
use Lcobucci\JWT\Token;

trait TokenMockingTrait
{
    private function createMockToken(string $userId): JsonWebToken
    {
        return new JsonWebToken(
            new Token(
                ['alg' => 'none'],
                ['uid' => new BasicClaim('uid', $userId)]
            ),
            true
        );
    }
}
