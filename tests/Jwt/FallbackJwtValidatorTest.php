<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use Lcobucci\JWT\Token;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class FallbackJwtValidatorTest extends TestCase
{
    /**
     * @var StringLiteral
     */
    private $tokenString;

    /**
     * @var MockObject|JwtValidatorInterface
     */
    private $primaryValidator;

    /**
     * @var MockObject|JwtValidatorInterface
     */
    private $secondaryValidator;

    /**
     * @var FallbackJwtValidator
     */
    private $fallbackValidator;

    /**
     * @var Udb3Token
     */
    private $token;


    public function setUp(): void
    {
        $this->tokenString = new StringLiteral(
            rtrim(
                file_get_contents(__DIR__ . '/samples/token.txt'),
                '\\r\\n'
            )
        );

        $this->primaryValidator = $this->createMock(JwtValidatorInterface::class);
        $this->secondaryValidator = $this->createMock(JwtValidatorInterface::class);

        $this->fallbackValidator = new FallbackJwtValidator(
            $this->primaryValidator,
            $this->secondaryValidator
        );

        $this->token = new Udb3Token(new Token());
    }

    /**
     * @test
     */
    public function it_uses_primary_validator_for_validation(): void
    {
        $this->primaryValidator->expects($this->once())
            ->method('validateTimeSensitiveClaims')
            ->with($this->token)
            ->willReturn(true);

        $this->secondaryValidator->expects($this->never())
            ->method('validateTimeSensitiveClaims');

        $this->assertTrue($this->fallbackValidator->validateTimeSensitiveClaims($this->token));
    }

    /**
     * @test
     */
    public function it_fall_back_to_secondary_validator_for_validation(): void
    {
        $this->primaryValidator->expects($this->once())
            ->method('validateTimeSensitiveClaims')
            ->with($this->token)
            ->willReturn(false);

        $this->secondaryValidator->expects($this->once())
            ->method('validateTimeSensitiveClaims')
            ->with($this->token)
            ->willReturn(true);

        $this->assertTrue($this->fallbackValidator->validateTimeSensitiveClaims($this->token));
    }

    /**
     * @test
     */
    public function it_uses_primary_validator_to_verify_claims(): void
    {
        $this->primaryValidator->expects($this->once())
            ->method('validateRequiredClaims')
            ->with($this->token)
            ->willReturn(true);

        $this->secondaryValidator->expects($this->never())
            ->method('validateRequiredClaims');

        $this->assertTrue($this->fallbackValidator->validateRequiredClaims($this->token));
    }

    /**
     * @test
     */
    public function it_fall_backs_to_secondary_validator_to_verify_claims(): void
    {
        $this->primaryValidator->expects($this->once())
            ->method('validateRequiredClaims')
            ->with($this->token)
            ->willReturn(false);

        $this->secondaryValidator->expects($this->once())
            ->method('validateRequiredClaims')
            ->with($this->token)
            ->willReturn(true);

        $this->assertTrue($this->fallbackValidator->validateRequiredClaims($this->token));
    }

    /**
     * @test
     */
    public function it_uses_primary_validator_to_verify_signature(): void
    {
        $this->primaryValidator->expects($this->once())
            ->method('verifySignature')
            ->with($this->token)
            ->willReturn(true);

        $this->secondaryValidator->expects($this->never())
            ->method('verifySignature');

        $this->assertTrue($this->fallbackValidator->verifySignature($this->token));
    }

    /**
     * @test
     */
    public function it_fall_backs_to_secondary_validator_to_verify_signature(): void
    {
        $this->primaryValidator->expects($this->once())
            ->method('verifySignature')
            ->with($this->token)
            ->willReturn(false);

        $this->secondaryValidator->expects($this->once())
            ->method('verifySignature')
            ->with($this->token)
            ->willReturn(true);

        $this->assertTrue($this->fallbackValidator->verifySignature($this->token));
    }
}
