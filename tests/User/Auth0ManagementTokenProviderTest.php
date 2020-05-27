<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\User;

use CultuurNet\UDB3\User\Auth0ManagementTokenGenerator;
use CultuurNet\UDB3\User\Auth0ManagementTokenProvider;
use CultuurNet\UDB3\User\Auth0ManagementTokenRepository;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;
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

        $parser = $this->createMock(Parser::class);

        $service = new Auth0ManagementTokenProvider(
            $tokenGenerator,
            $tokenRepository,
            $parser
        );

        $result = $service->token();

        $this->assertEquals('Token', $result);
    }

    /**
     * @test
     */
    public function it_returns_token_if_valid_token_is_in_repository()
    {
        $tokenRepository = $this->createMock(Auth0ManagementTokenRepository::class);
        $tokenRepository->expects($this->atLeast(1))
            ->method('token')
            ->willReturn('Token');

        $tokenGenerator = $this->createMock(Auth0ManagementTokenGenerator::class);
        $tokenGenerator->expects($this->never())
            ->method('newToken');

        $token = $this->createMock(Token::class);
        $token->expects($this->atLeast(1))
            ->method('isExpired')
            ->willReturn(false);

        $parser = $this->createMock(Parser::class);
        $parser->expects($this->atLeast(1))
            ->method('parse')
            ->with('Token')
            ->willReturn($token);

        $service = new Auth0ManagementTokenProvider(
            $tokenGenerator,
            $tokenRepository,
            $parser
        );

        $result = $service->token();

        $this->assertEquals('Token', $result);
    }

    /**
     * @test
     */
    public function it_generates_new_token_if_current_on_is_expired()
    {
        $tokenRepository = $this->createMock(Auth0ManagementTokenRepository::class);
        $tokenRepository->expects($this->atLeast(1))
            ->method('token')
            ->willReturn('Token');

        $tokenGenerator = $this->createMock(Auth0ManagementTokenGenerator::class);
        $tokenGenerator->expects($this->atLeast(1))
            ->method('newToken')
            ->willReturn('New-Token');

        $token = $this->createMock(Token::class);
        $token->expects($this->atLeast(1))
            ->method('isExpired')
            ->willReturn(true);

        $parser = $this->createMock(Parser::class);
        $parser->expects($this->atLeast(1))
            ->method('parse')
            ->with('Token')
            ->willReturn($token);

        $service = new Auth0ManagementTokenProvider(
            $tokenGenerator,
            $tokenRepository,
            $parser
        );

        $result = $service->token();

        $this->assertEquals('New-Token', $result);
    }

}
