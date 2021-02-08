<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use Exception;
use Lcobucci\JWT\Token;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class WithFallbackJwtDecoderTest extends TestCase
{
    /**
     * @var StringLiteral
     */
    private $tokenString;

    /**
     * @var MockObject|JwtDecoderServiceInterface
     */
    private $primaryDecoder;

    /**
     * @var MockObject|JwtDecoderServiceInterface
     */
    private $secondaryDecoder;

    /**
     * @var FallbackJwtDecoder
     */
    private $withFallBackJwtDecoder;

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

        $this->primaryDecoder = $this->createMock(JwtDecoderServiceInterface::class);
        $this->secondaryDecoder = $this->createMock(JwtDecoderServiceInterface::class);

        $this->withFallBackJwtDecoder = new FallbackJwtDecoder(
            $this->primaryDecoder,
            $this->secondaryDecoder
        );

        $this->token = new Udb3Token(new Token());
    }

    /**
     * @test
     */
    public function it_uses_primary_decoder_for_parsing(): void
    {
        $this->primaryDecoder->expects($this->once())
            ->method('parse')
            ->with($this->tokenString)
            ->willReturn($this->token);

        $this->secondaryDecoder->expects($this->never())
            ->method('parse');

        $result = $this->withFallBackJwtDecoder->parse($this->tokenString);
        $this->assertEquals($result, $this->token);
    }

    /**
     * @test
     */
    public function it_fall_backs_to_secondary_decoder(): void
    {
        $this->primaryDecoder->method('parse')
            ->willThrowException(new JwtParserException(new Exception()));

        $this->secondaryDecoder->expects($this->once())
            ->method('parse')
            ->with($this->tokenString)
            ->willReturn($this->token);

        $result = $this->withFallBackJwtDecoder->parse($this->tokenString);

        $this->assertEquals($result, $this->token);
    }

    /**
     * @test
     */
    public function it_uses_primary_decoder_for_validation(): void
    {
        $this->primaryDecoder->expects($this->once())
            ->method('validateData')
            ->with($this->token)
            ->willReturn(true);

        $this->secondaryDecoder->expects($this->never())
            ->method('validateData');

        $this->assertTrue($this->withFallBackJwtDecoder->validateData($this->token));
    }

    /**
     * @test
     */
    public function it_fall_back_to_secondary_decoder_for_validation(): void
    {
        $this->primaryDecoder->expects($this->once())
            ->method('validateData')
            ->with($this->token)
            ->willReturn(false);

        $this->secondaryDecoder->expects($this->once())
            ->method('validateData')
            ->with($this->token)
            ->willReturn(true);

        $this->assertTrue($this->withFallBackJwtDecoder->validateData($this->token));
    }

    /**
     * @test
     */
    public function it_uses_primary_decoder_to_verify_claims(): void
    {
        $this->primaryDecoder->expects($this->once())
            ->method('validateRequiredClaims')
            ->with($this->token)
            ->willReturn(true);

        $this->secondaryDecoder->expects($this->never())
            ->method('validateRequiredClaims');

        $this->assertTrue($this->withFallBackJwtDecoder->validateRequiredClaims($this->token));
    }

    /**
     * @test
     */
    public function it_fall_backs_to_secondary_decoder_to_verify_claims(): void
    {
        $this->primaryDecoder->expects($this->once())
            ->method('validateRequiredClaims')
            ->with($this->token)
            ->willReturn(false);

        $this->secondaryDecoder->expects($this->once())
            ->method('validateRequiredClaims')
            ->with($this->token)
            ->willReturn(true);

        $this->assertTrue($this->withFallBackJwtDecoder->validateRequiredClaims($this->token));
    }

    /**
     * @test
     */
    public function it_uses_primary_decoder_to_verify_signature(): void
    {
        $this->primaryDecoder->expects($this->once())
            ->method('verifySignature')
            ->with($this->token)
            ->willReturn(true);

        $this->secondaryDecoder->expects($this->never())
            ->method('verifySignature');

        $this->assertTrue($this->withFallBackJwtDecoder->verifySignature($this->token));
    }

    /**
     * @test
     */
    public function it_fall_backs_to_secondary_decoder_to_verify_signature(): void
    {
        $this->primaryDecoder->expects($this->once())
            ->method('verifySignature')
            ->with($this->token)
            ->willReturn(false);

        $this->secondaryDecoder->expects($this->once())
            ->method('verifySignature')
            ->with($this->token)
            ->willReturn(true);

        $this->assertTrue($this->withFallBackJwtDecoder->verifySignature($this->token));
    }
}
