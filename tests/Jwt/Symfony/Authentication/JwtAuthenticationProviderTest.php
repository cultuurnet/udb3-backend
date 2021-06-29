<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication;

use CultuurNet\UDB3\Jwt\JwtValidator;
use Lcobucci\JWT\Claim\Basic;
use Lcobucci\JWT\Token;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class JwtAuthenticationProviderTest extends TestCase
{
    /**
     * @var JwtValidator|MockObject
     */
    private $v1JwtValidator;

    /**
     * @var JwtValidator|MockObject
     */
    private $v2JwtValidator;

    /**
     * @var JwtAuthenticationProvider
     */
    private $authenticationProvider;

    public function setUp()
    {
        $this->v1JwtValidator = $this->createMock(JwtValidator::class);
        $this->v2JwtValidator = $this->createMock(JwtValidator::class);

        $this->authenticationProvider = new JwtAuthenticationProvider(
            $this->v1JwtValidator,
            $this->v2JwtValidator
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
                JsonWebTokenFactory::createWithClaims([])
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
        $token = JsonWebTokenFactory::createWithClaims([]);

        $this->v1JwtValidator->expects($this->once())
            ->method('verifySignature')
            ->with($token)
            ->willThrowException(new AuthenticationException());

        $this->v2JwtValidator->expects($this->once())
            ->method('verifySignature')
            ->with($token)
            ->willThrowException(new AuthenticationException());

        $this->expectException(AuthenticationException::class);

        $this->authenticationProvider->authenticate($token);
    }

    /**
     * @test
     */
    public function it_calls_the_validation_methods_on_the_v1_validator_if_the_signature_is_v1(): void
    {
        $token = JsonWebTokenFactory::createWithClaims([]);

        $this->v1JwtValidator->expects($this->once())
            ->method('verifySignature')
            ->with($token);

        $this->v2JwtValidator->expects($this->never())
            ->method('verifySignature');

        $this->v1JwtValidator->expects($this->once())
            ->method('validateClaims')
            ->with($token);

        $this->authenticationProvider->authenticate($token);
    }

    /**
     * @test
     */
    public function it_calls_the_claim_validation_method_on_the_v2_validator_if_the_signature_is_v2(): void
    {
        $token = JsonWebTokenFactory::createWithClaims(
            [
                'azp' => 'bla',
                'https://publiq.be/publiq-apis' => 'entry',
            ]
        );

        $this->v1JwtValidator->expects($this->once())
            ->method('verifySignature')
            ->with($token)
            ->willThrowException(new AuthenticationException());

        $this->v2JwtValidator->expects($this->once())
            ->method('verifySignature')
            ->with($token)
            ->willReturn(true);

        $this->v2JwtValidator->expects($this->once())
            ->method('validateClaims')
            ->with($token);

        $this->authenticationProvider->authenticate($token);
    }

    /**
     * @test
     */
    public function it_returns_an_authenticated_token_when_the_jwt_is_valid(): void
    {
        $token = JsonWebTokenFactory::createWithClaims(
            [
                'azp' => 'bla',
                'https://publiq.be/publiq-apis' => 'ups entry',
            ]
        );

        $this->v1JwtValidator->expects($this->once())
            ->method('verifySignature')
            ->with($token);

        $this->v2JwtValidator->expects($this->never())
            ->method('verifySignature');

        $this->v1JwtValidator->expects($this->once())
            ->method('validateClaims')
            ->with($token);

        $authToken = $this->authenticationProvider->authenticate($token);

        $this->assertEquals($token->getCredentials(), $authToken->getCredentials());
        $this->assertTrue($authToken->isAuthenticated());
    }
}
