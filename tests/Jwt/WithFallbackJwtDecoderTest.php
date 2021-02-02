<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use Lcobucci\JWT\Token;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use ValueObjects\StringLiteral\StringLiteral;

class WithFallbackJwtDecoderTest extends TestCase
{
    /**
     * @var StringLiteral
     */
    private $tokenString;
    /**
     * @var ObjectProphecy
     */
    private $primaryDecoder;

    /**
     * @var ObjectProphecy
     */
    private $secondaryDecoder;
    /**
     * @var Token
     */
    private $token;


    public function setUp()
    {
        $this->tokenString = new StringLiteral(
            rtrim(
                file_get_contents(__DIR__ . '/samples/token.txt'),
                '\\r\\n'
            )
        );

        $this->primaryDecoder = $this->prophesize(JwtDecoderServiceInterface::class);
        $this->secondaryDecoder = $this->prophesize(JwtDecoderServiceInterface::class);
        $this->token = new Udb3Token(new Token());
    }

    /**
     * @test
     */
    public function it_uses_primary_decoder_for_parsing()
    {
        $this->primaryDecoder->parse($this->tokenString)->willReturn($this->token);

        $withFallBackJwtDecoder = new FallbackJwtDecoder(
            $this->primaryDecoder->reveal(),
            $this->secondaryDecoder->reveal()
        );

        $result = $withFallBackJwtDecoder->parse($this->tokenString);
        $this->assertEquals($result, $this->token);

        $this->secondaryDecoder->parse($this->tokenString)->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     */
    public function it_fall_backs_to_secondary_decoder()
    {
        $this->primaryDecoder->parse($this->tokenString)->willThrow(JwtParserException::class);
        $this->secondaryDecoder->parse($this->tokenString)->willReturn($this->token);

        $withFallBackJwtDecoder = new FallbackJwtDecoder(
            $this->primaryDecoder->reveal(),
            $this->secondaryDecoder->reveal()
        );

        $result = $withFallBackJwtDecoder->parse($this->tokenString);

        $this->assertEquals($result, $this->token);
    }

    /**
     * @test
     */
    public function it_uses_primary_decoder_for_validation()
    {
        $this->primaryDecoder->validateData($this->token)->willReturn(true);

        $withFallBackJwtDecoder = new FallbackJwtDecoder(
            $this->primaryDecoder->reveal(),
            $this->secondaryDecoder->reveal()
        );

        $this->assertTrue($withFallBackJwtDecoder->validateData($this->token));
        $this->secondaryDecoder->validateData($this->token)->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     */
    public function it_fall_back_to_secondary_decoder_for_validation()
    {
        $this->primaryDecoder->validateData($this->token)->willReturn(false);
        $this->primaryDecoder->validateData($this->token)->willReturn(true);

        $withFallBackJwtDecoder = new FallbackJwtDecoder(
            $this->primaryDecoder->reveal(),
            $this->secondaryDecoder->reveal()
        );

        $this->assertTrue($withFallBackJwtDecoder->validateData($this->token));
    }

    /**
     * @test
     */
    public function it_uses_primary_decoder_to_verify_claims()
    {
        $this->primaryDecoder->validateRequiredClaims($this->token)->willReturn(true);

        $withFallBackJwtDecoder = new FallbackJwtDecoder(
            $this->primaryDecoder->reveal(),
            $this->secondaryDecoder->reveal()
        );

        $this->assertTrue($withFallBackJwtDecoder->validateRequiredClaims($this->token));
        $this->secondaryDecoder->validateRequiredClaims($this->token)->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     */
    public function it_fall_backs_to_secondary_decoder_to_verify_claims()
    {
        $this->primaryDecoder->validateRequiredClaims($this->token)->willReturn(false);
        $this->secondaryDecoder->validateRequiredClaims($this->token)->willReturn(true);

        $withFallBackJwtDecoder = new FallbackJwtDecoder(
            $this->primaryDecoder->reveal(),
            $this->secondaryDecoder->reveal()
        );

        $this->assertTrue($withFallBackJwtDecoder->validateRequiredClaims($this->token));
    }

    /**
     * @test
     */
    public function it_uses_primary_decoder_to_verify_signature()
    {
        $this->primaryDecoder->verifySignature($this->token)->willReturn(true);

        $withFallBackJwtDecoder = new FallbackJwtDecoder(
            $this->primaryDecoder->reveal(),
            $this->secondaryDecoder->reveal()
        );

        $this->assertTrue($withFallBackJwtDecoder->verifySignature($this->token));
        $this->secondaryDecoder->verifySignature($this->token)->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     */
    public function it_fall_backs_to_secondary_decoder_to_verify_signature()
    {
        $this->primaryDecoder->verifySignature($this->token)->willReturn(false);
        $this->secondaryDecoder->verifySignature($this->token)->willReturn(true);

        $withFallBackJwtDecoder = new FallbackJwtDecoder(
            $this->primaryDecoder->reveal(),
            $this->secondaryDecoder->reveal()
        );

        $this->assertTrue($withFallBackJwtDecoder->verifySignature($this->token));
    }
}
