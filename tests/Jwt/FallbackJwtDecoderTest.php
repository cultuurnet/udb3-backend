<?php

namespace CultuurNet\UDB3\Jwt;

use Lcobucci\JWT\Token;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FallbackJwtDecoderTest extends TestCase
{
    /**
     * @var JwtDecoderServiceInterface|MockObject
     */
    private $primaryDecoder;

    /**
     * @var JwtDecoderServiceInterface|MockObject
     */
    private $secondaryDecoder;

    /**
     * @var FallbackJwtDecoder
     */
    private $decoder;

    /**
     * @var Udb3Token
     */
    private $token;

    public function setUp(): void
    {
        $this->primaryDecoder = $this->createMock(JwtDecoderServiceInterface::class);
        $this->secondaryDecoder = $this->createMock(JwtDecoderServiceInterface::class);
        $this->decoder = new FallbackJwtDecoder($this->primaryDecoder, $this->secondaryDecoder);
        $this->token = new Udb3Token(new Token());
    }

    /**
     * @test
     */
    public function it_considers_the_issuer_valid_if_the_primary_decoder_reports_it_is_valid(): void
    {
        $this->primaryDecoder->expects($this->once())
            ->method('validateIssuer')
            ->with($this->token)
            ->willReturn(true);

        $this->secondaryDecoder->expects($this->never())
            ->method('validateIssuer');

        $this->assertTrue($this->decoder->validateIssuer($this->token));
    }

    /**
     * @test
     */
    public function it_considers_the_issuer_valid_if_the_secondary_decoder_reports_it_is_valid(): void
    {
        $this->primaryDecoder->expects($this->once())
            ->method('validateIssuer')
            ->with($this->token)
            ->willReturn(false);

        $this->secondaryDecoder->expects($this->once())
            ->method('validateIssuer')
            ->with($this->token)
            ->willReturn(true);

        $this->assertTrue($this->decoder->validateIssuer($this->token));
    }

    /**
     * @test
     */
    public function it_considers_the_issuer_invalid_if_no_decoder_reports_it_is_valid(): void
    {
        $this->primaryDecoder->expects($this->once())
            ->method('validateIssuer')
            ->with($this->token)
            ->willReturn(false);

        $this->secondaryDecoder->expects($this->once())
            ->method('validateIssuer')
            ->with($this->token)
            ->willReturn(false);

        $this->assertFalse($this->decoder->validateIssuer($this->token));
    }
}
