<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\Auth0ClientAccessToken;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\MockTokenStringFactory;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\Token;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class JwtBaseValidatorTest extends TestCase
{
    /**
     * @var JwtBaseValidator
     */
    private $validator;

    public function setUp()
    {
        $this->validator = new JwtBaseValidator(
            MockTokenStringFactory::getPublicKey(),
            ['valid-issuer-1', 'valid-issuer-2']
        );
    }

    private function createValidToken(): Token
    {
        return new Auth0ClientAccessToken(
            MockTokenStringFactory::createWithClaims(
                [
                    'iat' => time() - 3600,
                    'nbf' => time() - 3600,
                    'exp' => time() + 3600,
                    'iss' => 'valid-issuer-1',
                    'sub' => 'mock-id@clients',
                    'azp' => 'mock-id',
                    'gty' => 'client-credentials',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function it_throws_if_the_token_is_expired(): void
    {
        $token = new Auth0ClientAccessToken(
            MockTokenStringFactory::createWithClaims(
                [
                    'iat' => time() - 3600,
                    'nbf' => time() - 3600,
                    'exp' => time() - 1800,
                    'iss' => 'valid-issuer-1',
                    'sub' => 'mock-id@clients',
                    'azp' => 'mock-id',
                    'gty' => 'client-credentials',
                ]
            )
        );

        $this->expectException(AuthenticationException::class);
        $this->validator->validateClaims($token);
    }

    /**
     * @test
     */
    public function it_throws_if_the_issuer_is_missing(): void
    {
        $token = new Auth0ClientAccessToken(
            MockTokenStringFactory::createWithClaims(
                [
                    'iat' => time() - 3600,
                    'nbf' => time() - 3600,
                    'exp' => time() - 1800,
                    'sub' => 'mock-id@clients',
                    'azp' => 'mock-id',
                    'gty' => 'client-credentials',
                ]
            )
        );

        $this->expectException(AuthenticationException::class);
        $this->validator->validateClaims($token);
    }

    /**
     * @test
     */
    public function it_throws_if_the_issuer_is_invalid(): void
    {
        $token = new Auth0ClientAccessToken(
            MockTokenStringFactory::createWithClaims(
                [
                    'iat' => time() - 3600,
                    'nbf' => time() - 3600,
                    'exp' => time() - 1800,
                    'iss' => 'invalid-issuer',
                    'sub' => 'mock-id@clients',
                    'azp' => 'mock-id',
                    'gty' => 'client-credentials',
                ]
            )
        );

        $this->expectException(AuthenticationException::class);
        $this->validator->validateClaims($token);
    }

    /**
     * @test
     */
    public function it_does_not_throw_if_all_claims_are_valid(): void
    {
        $token = $this->createValidToken();
        $this->validator->validateClaims($token);
        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function it_throws_if_the_signature_is_invalid(): void
    {
        $token = new Auth0ClientAccessToken(
            MockTokenStringFactory::createWithClaimsAndInvalidSignature(
                [
                    'iat' => time() - 3600,
                    'nbf' => time() - 3600,
                    'exp' => time() - 1800,
                    'iss' => 'valid-issuer-1',
                    'sub' => 'mock-id@clients',
                    'azp' => 'mock-id',
                    'gty' => 'client-credentials',
                ]
            )
        );
        $this->expectException(AuthenticationException::class);
        $this->validator->verifySignature($token);
    }

    /**
     * @test
     */
    public function it_does_not_throw_if_the_signature_is_valid(): void
    {
        $token = $this->createValidToken();
        $this->validator->verifySignature($token);
        $this->addToAssertionCount(1);
    }
}
