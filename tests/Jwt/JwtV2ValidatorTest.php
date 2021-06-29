<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebTokenFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class JwtV2ValidatorTest extends TestCase
{
    /**
     * @var JsonWebTokenFactory
     */
    private $tokenFactory;

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
        $this->tokenFactory = new JsonWebTokenFactory(
            file_get_contents(__DIR__ . '/samples/private.pem'),
            'secret'
        );

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
        $token = $this->tokenFactory->createWithClaims([]);

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
        $token = $this->tokenFactory->createWithClaims([]);

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
        $tokenWithPermission = $this->tokenFactory->createWithClaims(
            [
                'azp' => 'foobar',
                'https://publiq.be/publiq-apis' => 'ups entry',
            ]
        );

        $tokenWithoutPermission = $this->tokenFactory->createWithClaims(
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
        $tokenFromV2JwtProvider = $this->tokenFactory->createWithClaims(['aud' => 'vsCe0hXlLaR255wOrW56Fau7vYO5qvqD']);
        $tokenWithUnknownAud = $this->tokenFactory->createWithClaims(['aud' => 'foobar']);

        $this->v2Validator->validateClaims($tokenFromV2JwtProvider);
        $this->addToAssertionCount(1);

        $this->expectException(AuthenticationException::class);
        $this->v2Validator->validateClaims($tokenWithUnknownAud);
    }
}
