<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebToken;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class JwtBaseValidatorTest extends TestCase
{
    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var Sha256
     */
    private $signer;

    /**
     * @var Key
     */
    private $publicKey;

    /**
     * @var Key
     */
    private $privateKey;

    /**
     * @var JwtBaseValidator
     */
    private $validator;

    public function setUp()
    {
        $this->builder = new Builder();
        $this->signer = new Sha256();
        $this->publicKey = new Key(file_get_contents(__DIR__ . '/samples/public.pem'));
        $this->privateKey = new Key(file_get_contents(__DIR__ . '/samples/private.pem'), 'secret');

        $this->validator = new JwtBaseValidator(
            $this->signer,
            $this->publicKey,
            ['sub'],
            ['valid-issuer-1', 'valid-issuer-2']
        );
    }

    private function createTokenWithClaims(array $claims): JsonWebToken
    {
        $builder = clone $this->builder;
        foreach ($claims as $claim => $value) {
            $builder = $builder->withClaim($claim, $value);
        }
        return new JsonWebToken($builder->getToken($this->signer, $this->privateKey));
    }

    private function createValidToken(): JsonWebToken
    {
        return $this->createTokenWithClaims(
            [
                'iat' => time() - 3600,
                'nbf' => time() - 3600,
                'exp' => time() + 3600,
                'iss' => 'valid-issuer-1',
                'sub' => 'mock-id',
            ]
        );
    }

    /**
     * @test
     */
    public function it_throws_if_the_token_is_expired(): void
    {
        $token = $this->createTokenWithClaims(
            [
                'iat' => time() - 3600,
                'nbf' => time() - 3600,
                'exp' => time() - 1800,
            ]
        );

        $this->expectException(AuthenticationException::class);
        $this->validator->validateClaims($token);
    }

    /**
     * @test
     */
    public function it_throws_if_the_issuer_is_missing(): void
    {
        $token = $this->createTokenWithClaims(
            [
                'iat' => time() - 3600,
                'nbf' => time() - 3600,
                'exp' => time() + 3600,
            ]
        );

        $this->expectException(AuthenticationException::class);
        $this->validator->validateClaims($token);
    }

    /**
     * @test
     */
    public function it_throws_if_the_issuer_is_invalid(): void
    {
        $token = $this->createTokenWithClaims(
            [
                'iat' => time() - 3600,
                'nbf' => time() - 3600,
                'exp' => time() + 3600,
                'iss' => 'invalid-issuer',
            ]
        );

        $this->expectException(AuthenticationException::class);
        $this->validator->validateClaims($token);
    }

    /**
     * @test
     */
    public function it_throws_if_a_required_claim_is_missing(): void
    {
        $token = $this->createTokenWithClaims(
            [
                'iat' => time() - 3600,
                'nbf' => time() - 3600,
                'exp' => time() + 3600,
                'iss' => 'valid-issuer-1',
            ]
        );

        $this->expectException(AuthenticationException::class);
        $this->validator->validateClaims($token);
    }

    /**
     * @test
     */
    public function it_does_not_throw_if_all_claims_are_valid(): void
    {
        $token = $this->createTokenWithClaims(
            [
                'iat' => time() - 3600,
                'nbf' => time() - 3600,
                'exp' => time() + 3600,
                'iss' => 'valid-issuer-1',
                'sub' => 'auth0|mock-id',
            ]
        );

        $this->validator->validateClaims($token);
        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function it_throws_if_the_signature_is_invalid(): void
    {
        $token = new JsonWebToken(
            $this->builder->getToken(
                $this->signer,
                new Key(file_get_contents(__DIR__ . '/samples/private-invalid.pem'))
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
