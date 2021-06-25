<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

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

    public function validateTimeSensitiveClaims(Udb3Token $jwt): bool
    {
        if ($this->primary->validateTimeSensitiveClaims($jwt)) {
            return true;
        }

        return $this->fallbackDecoder->validateTimeSensitiveClaims($jwt);
    }

    public function validateRequiredClaims(Udb3Token $udb3Token): bool
    {
        if ($this->primary->validateRequiredClaims($udb3Token)) {
            return true;
        }

        return $this->fallbackDecoder->validateRequiredClaims($udb3Token);
    }

    public function validateIssuer(Udb3Token $udb3Token): bool
    {
        return $this->primary->validateIssuer($udb3Token) || $this->fallbackDecoder->validateIssuer($udb3Token);
    }

    public function verifySignature(Udb3Token $udb3Token): bool
    {
        if ($this->primary->verifySignature($udb3Token)) {
            return true;
        }

        return $this->fallbackDecoder->verifySignature($udb3Token);
    }
}
