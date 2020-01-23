<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\User;

use Auth0\SDK\Auth0;
use CultuurNet\UDB3\User\Auth0ManagementTokenGenerator;
use CultuurNet\UDB3\User\Auth0ManagementTokenProvider;
use CultuurNet\UDB3\User\Auth0ManagementTokenRepository;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class Auth0ManagementTokenProviderTest extends TestCase
{

    /**
     * @test
     */
    public function it_generates_new_token_if_no_token_in_repository()
    {
        $tokenRepository = $this->createMock(Auth0ManagementTokenRepository::class);
        $tokenRepository->expects($this->atLeast(1))
            ->method('token')
            ->willReturn(null);

        $tokenRepository->expects($this->once())
            ->method('store');

        $tokenGenerator = $this->createMock(Auth0ManagementTokenGenerator::class);
        $tokenGenerator->expects($this->atLeast(1))
            ->method('newToken')
            ->willReturn('Token');

        $service = new Auth0ManagementTokenProvider(
            $tokenGenerator,
            $tokenRepository
        );

        $result = $service->token();

        $this->assertEquals('Token', $result);
    }

    /**
     * @test
     */
    public function it_returns_token_if_token_in_repository()
    {
        $tokenRepository = $this->createMock(Auth0ManagementTokenRepository::class);
        $tokenRepository->expects($this->atLeast(1))
            ->method('token')
            ->willReturn('Token');

        $tokenGenerator = $this->createMock(Auth0ManagementTokenGenerator::class);
        $tokenGenerator->expects($this->never())
            ->method('newToken');

        $service = new Auth0ManagementTokenProvider(
            $tokenGenerator,
            $tokenRepository
        );

        $result = $service->token();

        $this->assertEquals('Token', $result);
    }
}
