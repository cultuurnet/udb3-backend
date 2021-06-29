<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebToken;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class JwtV2ValidatorTest extends TestCase
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
    private $privateKey;

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
        $this->builder = new Builder();
        $this->signer = new Sha256();
        $this->privateKey = new Key(file_get_contents(__DIR__ . '/samples/private.pem'), 'secret');

        $this->baseValidator = $this->createMock(JwtValidator::class);
        $this->v2Validator = new JwtV2Validator(
            $this->baseValidator,
            'vsCe0hXlLaR255wOrW56Fau7vYO5qvqD'
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

    /**
     * @test
     */
    public function it_verifies_the_signature_via_the_decoratee(): void
    {
        $token = $this->createTokenWithClaims([]);

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
        $token = $this->createTokenWithClaims([]);

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
    public function it_verifies_the_permission_to_use_entry_api_if_azp_claim_is_present(): void
    {
        $tokenWithPermission = $this->createTokenWithClaims(
            [
                'azp' => 'foobar',
                'https://publiq.be/publiq-apis' => 'ups entry',
            ]
        );

        $tokenWithoutPermission = $this->createTokenWithClaims(
            [
                'azp' => 'foobar',
                'https://publiq.be/publiq-apis' => 'ups',
            ]
        );

        $this->v2Validator->validateClaims($tokenWithPermission);
        $this->addToAssertionCount(1);

        $this->expectException(AuthenticationException::class);
        $this->v2Validator->validateClaims($tokenWithoutPermission);
    }

    /**
     * @test
     */
    public function it_verifies_that_the_aud_is_the_v2_jwt_provider_if_no_azp_is_present(): void
    {
        $tokenFromV2JwtProvider = $this->createTokenWithClaims(['aud' => 'vsCe0hXlLaR255wOrW56Fau7vYO5qvqD']);
        $tokenWithUnknownAud = $this->createTokenWithClaims(['aud' => 'foobar']);

        $this->v2Validator->validateClaims($tokenFromV2JwtProvider);
        $this->addToAssertionCount(1);

        $this->expectException(AuthenticationException::class);
        $this->v2Validator->validateClaims($tokenWithUnknownAud);
    }
}
