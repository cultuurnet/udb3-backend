<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use ValueObjects\StringLiteral\StringLiteral;

/**
 * Class FallbackJwtDecoder
 * @package CultuurNet\UDB3\Jwt
 *
 * A wrapper class that enables backwards compatibility
 * with old Authorization tokens
 */
class FallbackJwtDecoder implements JwtDecoderServiceInterface
{

    /**
     * @var JwtDecoderServiceInterface
     */
    private $primary;

    /**
     * @var JwtDecoderServiceInterface
     */
    private $fallbackDecoder;

    public function __construct(
        JwtDecoderServiceInterface $jwtDecoderService,
        JwtDecoderServiceInterface $newDecoderService
    ) {
        $this->primary = $jwtDecoderService;
        $this->fallbackDecoder = $newDecoderService;
    }

    public function parse(StringLiteral $tokenString) : Udb3Token
    {
        try {
            return $this->primary->parse($tokenString);
        } catch (JwtParserException $e) {
            return $this->fallbackDecoder->parse($tokenString);
        }
    }

    public function validateData(Udb3Token $jwt) : bool
    {
        if ($this->primary->validateData($jwt)) {
            return true;
        }

        return $this->fallbackDecoder->validateData($jwt);
    }

    public function validateRequiredClaims(Udb3Token $udb3Token): bool
    {
        if ($this->primary->validateRequiredClaims($udb3Token)) {
            return true;
        }

        return $this->fallbackDecoder->validateRequiredClaims($udb3Token);
    }

    public function verifySignature(Udb3Token $udb3Token): bool
    {
        if ($this->primary->verifySignature($udb3Token)) {
            return true;
        }

        return $this->fallbackDecoder->verifySignature($udb3Token);
    }
}
