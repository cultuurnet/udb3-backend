<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Auth\Jwt;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\SampleFiles;
use PHPUnit\Framework\TestCase;

class UitIdV2JwtValidatorTest extends TestCase
{
    private UitIdV2JwtValidator $v2Validator;

    protected function setUp(): void
    {
        $this->v2Validator = new UitIdV2JwtValidator(
            SampleFiles::read(__DIR__ . '/samples/public.pem'),
            ['mock-issuer'],
            'vsCe0hXlLaR255wOrW56Fau7vYO5qvqD'
        );
    }

    /**
     * @test
     */
    public function it_verifies_the_signature_via_the_decoratee(): void
    {
        $token = JsonWebTokenFactory::createWithInvalidSignature();
        $this->expectException(ApiProblem::class);
        $this->v2Validator->verifySignature($token);
    }

    /**
     * @test
     */
    public function it_verifies_the_basic_claims_via_the_decoratee(): void
    {
        $token = JsonWebTokenFactory::createWithClaims([]);
        $this->expectException(ApiProblem::class);
        $this->v2Validator->validateClaims($token);
    }

    /**
     * @test
     */
    public function it_verifies_the_permission_to_use_entry_api_if_azp_claim_is_present(): void
    {
        $tokenWithPermission = JsonWebTokenFactory::createWithClaims(
            [
                'iss' => 'mock-issuer',
                'azp' => 'foobar',
                'sub' => 'mock',
                'https://publiq.be/publiq-apis' => 'ups entry',
            ]
        );

        $tokenWithoutPermission = JsonWebTokenFactory::createWithClaims(
            [
                'iss' => 'mock-issuer',
                'azp' => 'foobar',
                'sub' => 'mock',
                'https://publiq.be/publiq-apis' => 'ups',
            ]
        );

        $this->v2Validator->validateClaims($tokenWithPermission);
        $this->addToAssertionCount(1);

        $this->expectException(ApiProblem::class);
        $this->v2Validator->validateClaims($tokenWithoutPermission);
    }

    /**
     * @test
     */
    public function it_verifies_that_the_aud_is_the_v2_jwt_provider_if_no_azp_is_present(): void
    {
        $tokenFromV2JwtProvider = JsonWebTokenFactory::createWithClaims(
            [
                'iss' => 'mock-issuer',
                'sub' => 'mock',
                'aud' => 'vsCe0hXlLaR255wOrW56Fau7vYO5qvqD',
            ]
        );
        $tokenWithUnknownAud = JsonWebTokenFactory::createWithClaims(
            [
                'iss' => 'mock-issuer',
                'sub' => 'mock',
                'aud' => 'foobar',
            ]
        );

        $this->v2Validator->validateClaims($tokenFromV2JwtProvider);
        $this->addToAssertionCount(1);

        $this->expectException(ApiProblem::class);
        $this->v2Validator->validateClaims($tokenWithUnknownAud);
    }
}
