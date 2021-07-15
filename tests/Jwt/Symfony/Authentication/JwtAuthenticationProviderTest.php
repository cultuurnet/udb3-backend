<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication;

use Auth0\SDK\API\Management;
use CultuurNet\UDB3\Jwt\JwtValidator;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\AbstractToken;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\Auth0ClientAccessToken;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\Auth0UserAccessToken;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\JwtProviderV1Token;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\JwtProviderV2Token;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\MockTokenStringFactory;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\Token;
use CultuurNet\UDB3\User\Auth0UserIdentityResolver;
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

    private function getExpectedValidatorForTokenType(Token $token): MockObject
    {
        if ($token instanceof JwtProviderV1Token) {
            return $this->v1JwtValidator;
        }
        return $this->v2JwtValidator;
    }

    private function getUnusedValidatorForTokenType(Token $token): MockObject
    {
        if ($token instanceof JwtProviderV1Token) {
            return $this->v2JwtValidator;
        }
        return $this->v1JwtValidator;
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
                new JwtProviderV1Token(
                    MockTokenStringFactory::createWithClaims(
                        [
                            'uid' => 'a9b59309-8409-4f2a-a48c-b3e74f61c003',
                            'nick' => 'foo',
                            'email' => 'foo@example.com',
                        ]
                    )
                )
            )
        );

        $this->assertTrue(
            $this->authenticationProvider->supports(
                new JwtProviderV2Token(
                    MockTokenStringFactory::createWithClaims(
                        [
                            'sub' => 'auth0|a9b59309-8409-4f2a-a48c-b3e74f61c003',
                            'nickname' => 'foo',
                            'email' => 'foo@example.com',
                        ]
                    )
                )
            )
        );

        $this->assertTrue(
            $this->authenticationProvider->supports(
                new Auth0UserAccessToken(
                    MockTokenStringFactory::createWithClaims(
                        [
                            'sub' => 'auth0|9491aedb-7955-4c32-9aae-a692f47c6de0',
                            'azp' => 'mock-client-id',
                        ]
                    ),
                    new Auth0UserIdentityResolver(
                        $this->createMock(Management::class)
                    )
                )
            )
        );

        $this->assertTrue(
            $this->authenticationProvider->supports(
                new Auth0ClientAccessToken(
                    MockTokenStringFactory::createWithClaims(
                        [
                            'sub' => 'mock-client-id@clients',
                            'azp' => 'mock-client-id',
                            'gty' => 'client-credentials',
                        ]
                    )
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

    public function tokenDataProvider(): array
    {
        return [
            'v1' => [
                new JwtProviderV1Token(
                    MockTokenStringFactory::createWithClaims(
                        [
                            'uid' => 'a9b59309-8409-4f2a-a48c-b3e74f61c003',
                            'nick' => 'foo',
                            'email' => 'foo@example.com',
                        ]
                    )
                )
            ],
            'v2' => [
                new Auth0ClientAccessToken(
                    MockTokenStringFactory::createWithClaims(
                        [
                            'sub' => 'mock-client-id@clients',
                            'azp' => 'mock-client-id',
                            'gty' => 'client-credentials',
                        ]
                    )
                )
            ],
        ];
    }

    /**
     * @test
     * @dataProvider tokenDataProvider
     */
    public function it_throws_an_exception_when_the_jwt_signature_is_invalid_for_the_expected_token_version(
        AbstractToken $token
    ): void {
        $this->getExpectedValidatorForTokenType($token)->expects($this->once())
            ->method('verifySignature')
            ->with($token)
            ->willThrowException(new AuthenticationException());

        $this->getUnusedValidatorForTokenType($token)->expects($this->never())
            ->method('verifySignature');

        $this->expectException(AuthenticationException::class);

        $this->authenticationProvider->authenticate($token);
    }

    /**
     * @test
     * @dataProvider tokenDataProvider
     */
    public function it_calls_the_validation_methods_on_the_right_validator_depending_on_the_token_version(
        AbstractToken $token
    ): void {
        $this->getExpectedValidatorForTokenType($token)->expects($this->once())
            ->method('verifySignature')
            ->with($token);

        $this->getUnusedValidatorForTokenType($token)->expects($this->never())
            ->method('verifySignature');

        $this->getExpectedValidatorForTokenType($token)->expects($this->once())
            ->method('validateClaims')
            ->with($token)
            ->willThrowException(new AuthenticationException());

        $this->getUnusedValidatorForTokenType($token)->expects($this->never())
            ->method('validateClaims');

        $this->expectException(AuthenticationException::class);

        $this->authenticationProvider->authenticate($token);
    }

    /**
     * @test
     * @dataProvider tokenDataProvider
     */
    public function it_returns_an_authenticated_token_when_the_jwt_is_valid(
        AbstractToken $token
    ): void {
        $this->getExpectedValidatorForTokenType($token)->expects($this->once())
            ->method('verifySignature')
            ->with($token);

        $this->getUnusedValidatorForTokenType($token)->expects($this->never())
            ->method('verifySignature');

        $this->getExpectedValidatorForTokenType($token)->expects($this->once())
            ->method('validateClaims')
            ->with($token);

        $this->getUnusedValidatorForTokenType($token)->expects($this->never())
            ->method('validateClaims');

        $authToken = $this->authenticationProvider->authenticate($token);

        $this->assertEquals($token->getCredentials(), $authToken->getCredentials());
        $this->assertTrue($authToken->isAuthenticated());
    }
}
