<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication;

use CultuurNet\UDB3\Jwt\JwtValidatorInterface;
use CultuurNet\UDB3\Jwt\Udb3Token;
use Lcobucci\JWT\Claim\Basic;
use Lcobucci\JWT\Token as Jwt;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class JwtAuthenticationProviderTest extends TestCase
{
    /**
     * @var JwtValidatorInterface|MockObject
     */
    private $jwtValidator;

    /**
     * @var JwtAuthenticationProvider
     */
    private $authenticationProvider;

    public function setUp()
    {
        $this->jwtValidator = $this->createMock(JwtValidatorInterface::class);

        $this->authenticationProvider = new JwtAuthenticationProvider(
            $this->jwtValidator,
            'vsCe0hXlLaR255wOrW56Fau7vYO5qvqD'
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

        $this->jwtValidator->expects($this->once())
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

        $this->jwtValidator->expects($this->once())
            ->method('verifySignature')
            ->with($jwt)
            ->willReturn(true);

        $this->jwtValidator->expects($this->once())
            ->method('validateTimeSensitiveClaims')
            ->with($jwt)
            ->willReturn(false);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage(
            'Token expired (or not yet usable).'
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

        $this->jwtValidator->expects($this->once())
            ->method('verifySignature')
            ->with($jwt)
            ->willReturn(true);

        $this->jwtValidator->expects($this->once())
            ->method('validateTimeSensitiveClaims')
            ->with($jwt)
            ->willReturn(true);

        $this->jwtValidator->expects($this->once())
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
    public function it_throws_an_exception_when_the_jwt_has_an_invalid_claim(): void
    {
        $jwt = new Udb3Token(new Jwt());
        $token = new JwtUserToken($jwt);

        $this->jwtValidator->expects($this->once())
            ->method('verifySignature')
            ->with($jwt)
            ->willReturn(true);

        $this->jwtValidator->expects($this->once())
            ->method('validateTimeSensitiveClaims')
            ->with($jwt)
            ->willReturn(true);

        $this->jwtValidator->expects($this->once())
            ->method('validateRequiredClaims')
            ->with($jwt)
            ->willReturn(true);

        $this->jwtValidator->expects($this->once())
            ->method('validateIssuer')
            ->with($jwt)
            ->willReturn(false);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage(
            'Token is not issued by a valid issuer.'
        );

        $this->authenticationProvider->authenticate($token);
    }

    public function it_throws_if_the_azp_claim_is_missing_and_the_token_is_not_from_the_jwt_provider(): void
    {
        $jwt = new Udb3Token(
            new Jwt(
                ['alg' => 'none'],
                [
                    'aud' => new Basic('aud', 'bla'),
                ]
            )
        );

        $token = new JwtUserToken($jwt);

        $this->jwtValidator->expects($this->once())
            ->method('verifySignature')
            ->with($jwt)
            ->willReturn(true);

        $this->jwtValidator->expects($this->once())
            ->method('validateTimeSensitiveClaims')
            ->with($jwt)
            ->willReturn(true);

        $this->jwtValidator->expects($this->once())
            ->method('validateRequiredClaims')
            ->with($jwt)
            ->willReturn(true);

        $this->jwtValidator->expects($this->once())
            ->method('validateIssuer')
            ->with($jwt)
            ->willReturn(true);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage(
            'Only legacy id tokens are supported. Please use an access token instead.'
        );

        $this->authenticationProvider->authenticate($token);
    }

    /**
     * @test
     */
    public function it_throws_if_the_azp_claim_is_present_but_the_token_cannot_be_used_on_entry_api(): void
    {
        $jwt = new Udb3Token(
            new Jwt(
                ['alg' => 'none'],
                [
                    'azp' => new Basic('azp', 'Pwf7f2pSU3FsCCbGZz0gexx8NWOW9Hj9'),
                    'https://publiq.be/publiq-apis' => new Basic('https://publiq.be/publiq-apis', 'ups'),
                ]
            )
        );
        $token = new JwtUserToken($jwt);

        $this->jwtValidator->expects($this->once())
            ->method('verifySignature')
            ->with($jwt)
            ->willReturn(true);

        $this->jwtValidator->expects($this->once())
            ->method('validateTimeSensitiveClaims')
            ->with($jwt)
            ->willReturn(true);

        $this->jwtValidator->expects($this->once())
            ->method('validateRequiredClaims')
            ->with($jwt)
            ->willReturn(true);

        $this->jwtValidator->expects($this->once())
            ->method('validateIssuer')
            ->with($jwt)
            ->willReturn(true);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage(
            'The given token and its related client are not allowed to access EntryAPI.'
        );

        $this->authenticationProvider->authenticate($token);
    }

    /**
     * @test
     */
    public function it_returns_an_authenticated_token_when_the_jwt_is_valid(): void
    {
        $jwt = new Udb3Token(
            new Jwt(
                ['alg' => 'none'],
                [
                    'azp' => new Basic('azp', 'Pwf7f2pSU3FsCCbGZz0gexx8NWOW9Hj9'),
                    'https://publiq.be/publiq-apis' => new Basic('https://publiq.be/publiq-apis', 'ups entry'),
                ]
            )
        );
        $token = new JwtUserToken($jwt);

        $this->jwtValidator->expects($this->once())
            ->method('verifySignature')
            ->with($jwt)
            ->willReturn(true);

        $this->jwtValidator->expects($this->once())
            ->method('validateTimeSensitiveClaims')
            ->with($jwt)
            ->willReturn(true);

        $this->jwtValidator->expects($this->once())
            ->method('validateRequiredClaims')
            ->with($jwt)
            ->willReturn(true);

        $this->jwtValidator->expects($this->once())
            ->method('validateIssuer')
            ->with($jwt)
            ->willReturn(true);

        $authToken = $this->authenticationProvider->authenticate($token);

        $this->assertEquals($jwt, $authToken->getCredentials());
        $this->assertTrue($authToken->isAuthenticated());
    }
}
