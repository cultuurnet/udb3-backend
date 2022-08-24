<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication;

use CultuurNet\UDB3\Jwt\JwtValidator;
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

    private function getExpectedValidatorForTokenType(JsonWebToken $token): MockObject
    {
        if ($token->getType() === JsonWebToken::UIT_ID_V1_JWT_PROVIDER_TOKEN) {
            return $this->v1JwtValidator;
        }
        return $this->v2JwtValidator;
    }

    private function getUnusedValidatorForTokenType(JsonWebToken $token): MockObject
    {
        if ($token->getType() === JsonWebToken::UIT_ID_V1_JWT_PROVIDER_TOKEN) {
            return $this->v2JwtValidator;
        }
        return $this->v1JwtValidator;
    }

    public function tokenDataProvider(): array
    {
        return [
            'v1' => [JsonWebTokenFactory::createWithClaims(['uid' => 'mock-v1-id'])],
            'v2' => [JsonWebTokenFactory::createWithClaims(['sub' => 'auth0|mock-v2-id', 'azp' => 'mock-client'])],
        ];
    }

    /**
     * @test
     * @dataProvider tokenDataProvider
     */
    public function it_throws_an_exception_when_the_jwt_signature_is_invalid_for_the_expected_token_version(
        JsonWebToken $token
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
        JsonWebToken $token
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
    public function it_does_not_throw_when_the_jwt_is_valid(
        JsonWebToken $token
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

        $this->authenticationProvider->authenticate($token);

        $this->addToAssertionCount(1);
    }
}
