<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

/**
 * A wrapper class that enables backwards compatibility
 * with old Authorization tokens
 */
class FallbackJwtValidator implements JwtValidatorInterface
{
    /**
     * @var JwtValidatorInterface
     */
    private $primary;

    /**
     * @var JwtValidatorInterface
     */
    private $secondary;

    public function __construct(
        JwtValidatorInterface $primary,
        JwtValidatorInterface $secondary
    ) {
        $this->primary = $primary;
        $this->secondary = $secondary;
    }

    public function validateTimeSensitiveClaims(Udb3Token $jwt): bool
    {
        if ($this->primary->validateTimeSensitiveClaims($jwt)) {
            return true;
        }

        return $this->secondary->validateTimeSensitiveClaims($jwt);
    }

    public function validateRequiredClaims(Udb3Token $udb3Token): bool
    {
        if ($this->primary->validateRequiredClaims($udb3Token)) {
            return true;
        }

        return $this->secondary->validateRequiredClaims($udb3Token);
    }

    public function validateIssuer(Udb3Token $udb3Token): bool
    {
        return $this->primary->validateIssuer($udb3Token) || $this->secondary->validateIssuer($udb3Token);
    }

    public function verifySignature(Udb3Token $udb3Token): bool
    {
        if ($this->primary->verifySignature($udb3Token)) {
            return true;
        }

        return $this->secondary->verifySignature($udb3Token);
    }
}
