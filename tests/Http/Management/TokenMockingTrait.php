<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Management;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebToken;
use CultuurNet\UDB3\Jwt\Udb3Token;
use Lcobucci\JWT\Claim\Basic as BasicClaim;
use Lcobucci\JWT\Token;

trait TokenMockingTrait
{
    /**
     * @param string $userId
     *
     * @return JsonWebToken
     */
    private function createMockToken($userId): JsonWebToken
    {
        return new JsonWebToken(
            new Udb3Token(
                new Token(
                    ['alg' => 'none'],
                    ['uid' => new BasicClaim('uid', $userId)]
                )
            ),
            true
        );
    }
}
