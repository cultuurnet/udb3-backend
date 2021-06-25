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
    private $v1JwtValidator;

    /**
     * @var JwtValidatorInterface|MockObject
     */
    private $v2JwtValidator;

    /**
     * @var JwtAuthenticationProvider
     */
    private $authenticationProvider;

    public function setUp()
    {
        $this->v1JwtValidator = $this->createMock(JwtValidatorInterface::class);
        $this->v2JwtValidator = $this->createMock(JwtValidatorInterface::class);

        $this->authenticationProvider = new JwtAuthenticationProvider(
            $this->v1JwtValidator,
            $this->v2JwtValidator,
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

        $this->v1JwtValidator->expects($this->once())
            ->method('verifySignature')
            ->with($jwt)
            ->willThrowException(new AuthenticationException());

        $this->v2JwtValidator->expects($this->once())
            ->method('verifySignature')
            ->with($jwt)
            ->willThrowException(new AuthenticationException());

        $this->expectException(AuthenticationException::class);

        $this->authenticationProvider->authenticate($token);
    }

    /**
     * @test
     */
    public function it_calls_the_validation_methods_on_the_v1_validator_if_the_signature_is_v1(): void
    {
        $jwt = new Udb3Token(new Jwt());
        $token = new JwtUserToken($jwt);

        $this->v1JwtValidator->expects($this->once())
            ->method('verifySignature')
            ->with($jwt);

        $this->v2JwtValidator->expects($this->never())
            ->method('verifySignature');

        $this->v1JwtValidator->expects($this->once())
            ->method('validateClaims')
            ->with($jwt);

        $this->authenticationProvider->authenticate($token);
    }

    /**
     * @test
     */
    public function it_calls_the_claim_validation_method_on_the_v2_validator_if_the_signature_is_v2(): void
    {
        $jwt = new Udb3Token(
            new Jwt(
                ['alg' => 'none'],
                [
                    'azp' => new Basic('azp', 'bla'),
                    'https://publiq.be/publiq-apis' =>  new Basic('https://publiq.be/publiq-apis', 'entry'),
                ]
            )
        );
        $token = new JwtUserToken($jwt);

        $this->v1JwtValidator->expects($this->once())
            ->method('verifySignature')
            ->with($jwt)
            ->willThrowException(new AuthenticationException());

        $this->v2JwtValidator->expects($this->once())
            ->method('verifySignature')
            ->with($jwt)
            ->willReturn(true);

        $this->v2JwtValidator->expects($this->once())
            ->method('validateClaims')
            ->with($jwt);

        $this->authenticationProvider->authenticate($token);
    }

    /**
     * @test
     */
    public function it_throws_if_the_azp_claim_is_missing_from_v2_token_and_it_is_not_from_the_jwt_provider(): void
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

        $this->v1JwtValidator->expects($this->once())
            ->method('verifySignature')
            ->with($jwt)
            ->willThrowException(new AuthenticationException());

        $this->v2JwtValidator->expects($this->once())
            ->method('verifySignature')
            ->with($jwt);

        $this->v2JwtValidator->expects($this->once())
            ->method('validateClaims')
            ->with($jwt);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage(
            'Only legacy id tokens are supported. Please use an access token instead.'
        );

        $this->authenticationProvider->authenticate($token);
    }

    /**
     * @test
     */
    public function it_throws_if_the_token_is_v2_and_an_access_token_but_is_not_usable_on_entry_api(): void
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

        $this->v1JwtValidator->expects($this->once())
            ->method('verifySignature')
            ->with($jwt)
            ->willThrowException(new AuthenticationException());

        $this->v2JwtValidator->expects($this->once())
            ->method('verifySignature')
            ->with($jwt);

        $this->v2JwtValidator->expects($this->once())
            ->method('validateClaims')
            ->with($jwt);

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

        $this->v1JwtValidator->expects($this->once())
            ->method('verifySignature')
            ->with($jwt);

        $this->v2JwtValidator->expects($this->never())
            ->method('verifySignature');

        $this->v1JwtValidator->expects($this->once())
            ->method('validateClaims')
            ->with($jwt);

        $authToken = $this->authenticationProvider->authenticate($token);

        $this->assertEquals($jwt, $authToken->getCredentials());
        $this->assertTrue($authToken->isAuthenticated());
    }
}
