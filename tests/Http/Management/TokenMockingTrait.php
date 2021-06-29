<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Management;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebToken;
use CultuurNet\UDB3\Jwt\Udb3Token;
use Lcobucci\JWT\Claim\Basic as BasicClaim;
use Lcobucci\JWT\Token as JwtToken;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;

trait TokenMockingTrait
{
    /**
     * @param string $userId
     *
     * @return JsonWebToken|MockObject
     */
    private function createMockToken($userId)
    {
        /** @var MockBuilder $mockBuilder */
        $mockBuilder = $this->getMockBuilder(JsonWebToken::class);

        /** @var JsonWebToken|MockObject $token */
        $token = $mockBuilder
            ->setMethods(['isAuthenticated', 'getCredentials'])
            ->setMockClassName('JwtUserToken')
            ->disableOriginalConstructor()
            ->getMock();

        $jwtCredentials = new Udb3Token(
            new JwtToken(
                ['alg' => 'none'],
                ['uid' => new BasicClaim('uid', $userId)]
            )
        );

        $token
            ->method('isAuthenticated')
            ->willReturn(true);

        $token
            ->method('getCredentials')
            ->willReturn($jwtCredentials);

        return $token;
    }
}
