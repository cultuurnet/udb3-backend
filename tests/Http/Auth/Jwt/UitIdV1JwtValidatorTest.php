<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Auth\Jwt;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class UitIdV1JwtValidatorTest extends TestCase
{
    private const USER_ID = 'c82bd40c-1932-4c45-bd5d-a76cc9907cee';

    private LoggerInterface&MockObject $logger;

    private UitIdV1JwtValidator $v1Validator;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->v1Validator = new UitIdV1JwtValidator(
            JsonWebTokenFactory::getPublicKey(),
            ['valid-issuer'],
            $this->logger
        );
    }

    private function createValidToken(array $claims): JsonWebToken
    {
        return JsonWebTokenFactory::createWithClaims(
            [
                'iat' => time() - 3600,
                'nbf' => time() - 3600,
                'exp' => time() + 3600,
                'iss' => 'valid-issuer',
            ] + $claims
        );
    }

    /**
     * @test
     */
    public function it_logs_the_user_of_a_v1_token(): void
    {
        $this->logger->expects($this->once())
            ->method('error')
            ->with(self::USER_ID . ' has used a v1-token e-mail: mock@example.com');

        $this->v1Validator->validateClaims(
            $this->createValidToken(
                [
                    'uid' => self::USER_ID,
                    'email' => 'mock@example.com',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function it_logs_the_user_of_a_v1_token_without_an_email_claim(): void
    {
        $this->logger->expects($this->once())
            ->method('error')
            ->with(self::USER_ID . ' has used a v1-token e-mail: Unknown');

        $this->v1Validator->validateClaims($this->createValidToken(['uid' => self::USER_ID]));
    }

    /**
     * @test
     */
    public function it_does_not_log_a_token_with_invalid_claims(): void
    {
        $expiredToken = JsonWebTokenFactory::createWithClaims(
            [
                'iat' => time() - 3600,
                'nbf' => time() - 3600,
                'exp' => time() - 1800,
                'iss' => 'valid-issuer',
                'uid' => self::USER_ID,
                'email' => 'mock@example.com',
            ]
        );

        $this->logger->expects($this->never())
            ->method('error');

        $this->expectException(ApiProblem::class);

        $this->v1Validator->validateClaims($expiredToken);
    }

    /**
     * @test
     */
    public function it_does_not_log_a_token_with_an_invalid_signature(): void
    {
        $this->logger->expects($this->never())
            ->method('error');

        $this->expectException(ApiProblem::class);

        $this->v1Validator->verifySignature(JsonWebTokenFactory::createWithInvalidSignature());
    }

    /**
     * @test
     */
    public function it_verifies_the_basic_claims_via_the_decoratee(): void
    {
        $this->expectException(ApiProblem::class);

        $this->v1Validator->validateClaims(JsonWebTokenFactory::createWithClaims([]));
    }

    /**
     * @test
     */
    public function it_requires_a_uid_claim(): void
    {
        $token = $this->createValidToken(['sub' => 'mock-id']);

        $this->logger->expects($this->never())
            ->method('error');

        try {
            $this->v1Validator->validateClaims($token);
            $this->fail('Expected an ApiProblem to be thrown.');
        } catch (ApiProblem $apiProblem) {
            $this->assertEquals('Token is missing one of its required claims.', $apiProblem->getDetail());
        }
    }
}
