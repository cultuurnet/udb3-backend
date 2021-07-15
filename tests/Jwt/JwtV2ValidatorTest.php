<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\Auth0ClientAccessToken;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\JwtProviderV2Token;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\MockTokenStringFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class JwtV2ValidatorTest extends TestCase
{
    /**
     * @var JwtValidator|MockObject
     */
    private $baseValidator;

    /**
     * @var JwtV2Validator
     */
    private $v2Validator;

    protected function setUp()
    {
        $this->baseValidator = $this->createMock(JwtValidator::class);
        $this->v2Validator = new JwtV2Validator(
            $this->baseValidator,
            'vsCe0hXlLaR255wOrW56Fau7vYO5qvqD'
        );
    }

    /**
     * @test
     */
    public function it_verifies_the_signature_via_the_decoratee(): void
    {
        $token = new Auth0ClientAccessToken(
            MockTokenStringFactory::createWithClaims(
                [
                    'iat' => time() - 3600,
                    'nbf' => time() - 3600,
                    'exp' => time() + 3600,
                    'iss' => 'valid-issuer-1',
                    'sub' => 'mock-id@clients',
                    'azp' => 'mock-id',
                    'gty' => 'client-credentials',
                    'https://publiq.be/publiq-apis' => 'entry',
                ]
            )
        );

        $this->baseValidator->expects($this->once())
            ->method('verifySignature')
            ->with($token)
            ->willThrowException(new AuthenticationException());

        $this->expectException(AuthenticationException::class);
        $this->v2Validator->verifySignature($token);
    }

    /**
     * @test
     */
    public function it_verifies_the_basic_claims_via_the_decoratee(): void
    {
        $token = new Auth0ClientAccessToken(
            MockTokenStringFactory::createWithClaims(
                [
                    'iat' => time() - 3600,
                    'nbf' => time() - 3600,
                    'exp' => time() + 3600,
                    'iss' => 'valid-issuer-1',
                    'sub' => 'mock-id@clients',
                    'azp' => 'mock-id',
                    'gty' => 'client-credentials',
                    'https://publiq.be/publiq-apis' => 'entry',
                ]
            )
        );

        $this->baseValidator->expects($this->once())
            ->method('validateClaims')
            ->with($token)
            ->willThrowException(new AuthenticationException());

        $this->expectException(AuthenticationException::class);
        $this->v2Validator->validateClaims($token);
    }

    /**
     * @test
     */
    public function it_verifies_the_permission_to_use_entry_api_if_the_token_is_an_access_token(): void
    {
        $tokenWithPermission = new Auth0ClientAccessToken(
            MockTokenStringFactory::createWithClaims(
                [
                    'iat' => time() - 3600,
                    'nbf' => time() - 3600,
                    'exp' => time() + 3600,
                    'iss' => 'valid-issuer-1',
                    'sub' => 'mock-id@clients',
                    'azp' => 'mock-id',
                    'gty' => 'client-credentials',
                    'https://publiq.be/publiq-apis' => 'ups entry sapi',
                ]
            )
        );

        $tokenWithoutPermission = new Auth0ClientAccessToken(
            MockTokenStringFactory::createWithClaims(
                [
                    'iat' => time() - 3600,
                    'nbf' => time() - 3600,
                    'exp' => time() + 3600,
                    'iss' => 'valid-issuer-1',
                    'sub' => 'mock-id@clients',
                    'azp' => 'mock-id',
                    'gty' => 'client-credentials',
                    'https://publiq.be/publiq-apis' => 'ups sapi',
                ]
            )
        );

        $this->v2Validator->validateClaims($tokenWithPermission);
        $this->addToAssertionCount(1);

        $this->expectException(AuthenticationException::class);
        $this->v2Validator->validateClaims($tokenWithoutPermission);
    }

    /**
     * @test
     */
    public function it_verifies_that_the_aud_is_the_v2_jwt_provider_if_v2_token_is_given(): void
    {
        $tokenFromV2JwtProvider = new JwtProviderV2Token(
            MockTokenStringFactory::createWithClaims(
                [
                    'iat' => time() - 3600,
                    'nbf' => time() - 3600,
                    'exp' => time() + 3600,
                    'iss' => 'valid-issuer-1',
                    'sub' => 'auth0|4d950177-7ea0-4ff1-b7d9-98047f110b10',
                    'nickname' => 'mock',
                    'email' => 'mock@example.com',
                    'aud' => 'vsCe0hXlLaR255wOrW56Fau7vYO5qvqD',
                    'https://publiq.be/publiq-apis' => 'ups entry sapi',
                ]
            )
        );

        $tokenWithUnknownAud = new JwtProviderV2Token(
            MockTokenStringFactory::createWithClaims(
                [
                    'iat' => time() - 3600,
                    'nbf' => time() - 3600,
                    'exp' => time() + 3600,
                    'iss' => 'valid-issuer-1',
                    'sub' => 'auth0|4d950177-7ea0-4ff1-b7d9-98047f110b10',
                    'nickname' => 'mock',
                    'email' => 'mock@example.com',
                    'aud' => 'foobar',
                    'https://publiq.be/publiq-apis' => 'ups entry sapi',
                ]
            )
        );

        $this->v2Validator->validateClaims($tokenFromV2JwtProvider);
        $this->addToAssertionCount(1);

        $this->expectException(AuthenticationException::class);
        $this->v2Validator->validateClaims($tokenWithUnknownAud);
    }
}
