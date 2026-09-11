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
            $claims + [
                'iat' => time() - 3600,
                'nbf' => time() - 3600,
                'exp' => time() + 3600,
                'iss' => 'valid-issuer',
            ]
        );
    }

    private function assertApiProblemDetail(string $expectedDetail, JsonWebToken $token): void
    {
        try {
            $this->v1Validator->validateClaims($token);
            $this->fail('Expected an ApiProblem to be thrown.');
        } catch (ApiProblem $apiProblem) {
            $this->assertEquals($expectedDetail, $apiProblem->getDetail());
        }
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
    public function it_does_not_log_an_expired_token(): void
    {
        $this->logger->expects($this->never())
            ->method('error');

        $this->assertApiProblemDetail(
            'Token expired (or not yet usable).',
            $this->createValidToken(
                [
                    'exp' => time() - 1800,
                    'uid' => self::USER_ID,
                    'email' => 'mock@example.com',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function it_does_not_log_a_token_from_an_invalid_issuer(): void
    {
        $this->logger->expects($this->never())
            ->method('error');

        $this->assertApiProblemDetail(
            'Token is not issued by a valid issuer.',
            $this->createValidToken(
                [
                    'iss' => 'invalid-issuer',
                    'uid' => self::USER_ID,
                    'email' => 'mock@example.com',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function it_does_not_log_a_token_without_a_uid_claim(): void
    {
        $this->logger->expects($this->never())
            ->method('error');

        $this->assertApiProblemDetail(
            'Token is missing one of its required claims.',
            $this->createValidToken(['sub' => 'mock-id'])
        );
    }

    /**
     * @test
     */
    public function it_accepts_a_valid_signature_without_logging(): void
    {
        $this->logger->expects($this->never())
            ->method('error');

        $this->v1Validator->verifySignature($this->createValidToken(['uid' => self::USER_ID]));

        $this->addToAssertionCount(1);
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
}
