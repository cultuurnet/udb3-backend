<?php

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication;

use CultuurNet\UDB3\Jwt\JwtDecoderServiceInterface;
use CultuurNet\UDB3\Jwt\Udb3Token;
use Lcobucci\JWT\Token as Jwt;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class JwtAuthenticationProviderTest extends TestCase
{
    /**
     * @var JwtDecoderServiceInterface|MockObject
     */
    private $decoderService;

    /**
     * @var JwtAuthenticationProvider
     */
    private $authenticationProvider;

    public function setUp()
    {
        $this->decoderService = $this->createMock(JwtDecoderServiceInterface::class);

        $this->authenticationProvider = new JwtAuthenticationProvider(
            $this->decoderService
        );
    }

    /**
     * @test
     */
    public function it_can_detect_which_token_it_supports()
    {
        $this->assertFalse(
            $this->authenticationProvider->supports(
                new AnonymousToken('key', 'user')
            )
        );

        $this->assertTrue(
            $this->authenticationProvider->supports(
                new JwtUserToken(
                    new Udb3Token(new Jwt())
                )
            )
        );
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_authenticating_an_unsupported_token()
    {
        $token = new AnonymousToken('key', 'user');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage(
            'Token type Symfony\\Component\\Security\\Core\\Authentication\\Token\\AnonymousToken not supported.'
        );

        $this->authenticationProvider->authenticate($token);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_the_jwt_signature_is_invalid()
    {
        $jwt = new Udb3Token(new Jwt());
        $token = new JwtUserToken($jwt);

        $this->decoderService->expects($this->once())
            ->method('verifySignature')
            ->with($jwt)
            ->willReturn(false);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage(
            'Token signature verification failed. The token is likely forged or manipulated.'
        );

        $this->authenticationProvider->authenticate($token);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_the_jwt_data_is_invalid()
    {
        $jwt = new Udb3Token(new Jwt());
        $token = new JwtUserToken($jwt);

        $this->decoderService->expects($this->once())
            ->method('verifySignature')
            ->with($jwt)
            ->willReturn(true);

        $this->decoderService->expects($this->once())
            ->method('validateData')
            ->with($jwt)
            ->willReturn(false);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage(
            'Token claims validation failed. This most likely means the token is expired.'
        );

        $this->authenticationProvider->authenticate($token);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_the_jwt_is_missing_required_claims()
    {
        $jwt = new Udb3Token(new Jwt());
        $token = new JwtUserToken($jwt);

        $this->decoderService->expects($this->once())
            ->method('verifySignature')
            ->with($jwt)
            ->willReturn(true);

        $this->decoderService->expects($this->once())
            ->method('validateData')
            ->with($jwt)
            ->willReturn(true);

        $this->decoderService->expects($this->once())
            ->method('validateRequiredClaims')
            ->with($jwt)
            ->willReturn(false);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage(
            'Token is missing one of its required claims.'
        );

        $this->authenticationProvider->authenticate($token);
    }

    /**
     * @test
     */
    public function it_returns_an_authenticated_token_when_the_jwt_is_valid(): void
    {
        $jwt = new Udb3Token(new Jwt());
        $token = new JwtUserToken($jwt);

        $this->decoderService->expects($this->once())
            ->method('verifySignature')
            ->with($jwt)
            ->willReturn(true);

        $this->decoderService->expects($this->once())
            ->method('validateData')
            ->with($jwt)
            ->willReturn(true);

        $this->decoderService->expects($this->once())
            ->method('validateRequiredClaims')
            ->with($jwt)
            ->willReturn(true);

        $authToken = $this->authenticationProvider->authenticate($token);

        $this->assertEquals($jwt, $authToken->getCredentials());
        $this->assertTrue($authToken->isAuthenticated());
    }
}
