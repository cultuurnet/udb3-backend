<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use PHPUnit\Framework\TestCase;

final class GenericJwtValidatorTest extends TestCase
{
    private GenericJwtValidator $validator;

    public function setUp()
    {
        $this->validator = new GenericJwtValidator(
            JsonWebTokenFactory::getPublicKey(),
            ['sub'],
            ['valid-issuer-1', 'valid-issuer-2']
        );
    }

    private function createValidToken(): JsonWebToken
    {
        return JsonWebTokenFactory::createWithClaims(
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
        $token = JsonWebTokenFactory::createWithClaims(
            [
                'iat' => time() - 3600,
                'nbf' => time() - 3600,
                'exp' => time() - 1800,
            ]
        );

        $this->expectException(ApiProblem::class);
        $this->validator->validateClaims($token);
    }

    /**
     * @test
     */
    public function it_throws_if_the_issuer_is_missing(): void
    {
        $token = JsonWebTokenFactory::createWithClaims(
            [
                'iat' => time() - 3600,
                'nbf' => time() - 3600,
                'exp' => time() + 3600,
            ]
        );

        $this->expectException(ApiProblem::class);
        $this->validator->validateClaims($token);
    }

    /**
     * @test
     */
    public function it_throws_if_the_issuer_is_invalid(): void
    {
        $token = JsonWebTokenFactory::createWithClaims(
            [
                'iat' => time() - 3600,
                'nbf' => time() - 3600,
                'exp' => time() + 3600,
                'iss' => 'invalid-issuer',
            ]
        );

        $this->expectException(ApiProblem::class);
        $this->validator->validateClaims($token);
    }

    /**
     * @test
     */
    public function it_throws_if_a_required_claim_is_missing(): void
    {
        $token = JsonWebTokenFactory::createWithClaims(
            [
                'iat' => time() - 3600,
                'nbf' => time() - 3600,
                'exp' => time() + 3600,
                'iss' => 'valid-issuer-1',
            ]
        );

        $this->expectException(ApiProblem::class);
        $this->validator->validateClaims($token);
    }

    /**
     * @test
     */
    public function it_does_not_throw_if_all_claims_are_valid(): void
    {
        $token = JsonWebTokenFactory::createWithClaims(
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
        $token = JsonWebTokenFactory::createWithInvalidSignature();
        $this->expectException(ApiProblem::class);
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
