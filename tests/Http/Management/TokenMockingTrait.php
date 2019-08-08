<?php

namespace CultuurNet\UDB3\Http\Management;

use CultuurNet\SymfonySecurityJwt\Authentication\JwtUserToken;
use Lcobucci\JWT\Claim\Basic as BasicClaim;
use Lcobucci\JWT\Token as JwtToken;

trait TokenMockingTrait
{
    /**
     * @param string $userId
     *
     * @return JwtUserToken|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockToken($userId)
    {
        /** @var \PHPUnit_Framework_MockObject_MockBuilder $mockBuilder */
        $mockBuilder = $this->getMockBuilder(JwtUserToken::class);

        /** @var JwtUserToken|\PHPUnit_Framework_MockObject_MockObject $token */
        $token = $mockBuilder
            ->setMethods(['isAuthenticated', 'getCredentials'])
            ->setMockClassName('JwtUserToken')
            ->disableOriginalConstructor()
            ->getMock();

        $jwtCredentials = new JwtToken(
            ['alg' => 'none'],
            ['uid' => new BasicClaim('uid', $userId)]
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
