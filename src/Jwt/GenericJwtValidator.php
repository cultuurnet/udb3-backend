<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;

final class GenericJwtValidator implements JwtValidator
{
    private string $publicKey;
    private array $requiredClaims;
    private array $validIssuers;

    /**
     * @param string[] $requiredClaims
     * @param string[] $validIssuers
     */
    public function __construct(
        string $publicKey,
        array $requiredClaims = [],
        array $validIssuers = []
    ) {
        $this->publicKey = $publicKey;
        $this->requiredClaims = $requiredClaims;
        $this->validIssuers = $validIssuers;

        if (count($requiredClaims) !== count(array_filter($this->requiredClaims, 'is_string'))) {
            throw new \InvalidArgumentException(
                'All required claims should be strings.'
            );
        }

        if (count($validIssuers) !== count(array_filter($this->validIssuers, 'is_string'))) {
            throw new \InvalidArgumentException(
                'All valid issuers should be strings.'
            );
        }
    }

    public function validateClaims(JsonWebToken $token): void
    {
        $this->validateTimeSensitiveClaims($token);
        $this->validateIssuer($token);
        $this->validateRequiredClaims($token);
    }

    private function validateTimeSensitiveClaims(JsonWebToken $token): void
    {
        if (!$token->isUsableAtCurrentTime()) {
            throw ApiProblem::unauthorized(
                'Token expired (or not yet usable).'
            );
        }
    }

    private function validateRequiredClaims(JsonWebToken $token): void
    {
        if (!$token->hasClaims($this->requiredClaims)) {
            throw ApiProblem::unauthorized(
                'Token is missing one of its required claims.'
            );
        }
    }

    private function validateIssuer(JsonWebToken $token): void
    {
        if (!$token->hasValidIssuer($this->validIssuers)) {
            throw ApiProblem::unauthorized(
                'Token is not issued by a valid issuer.'
            );
        }
    }

    public function verifySignature(JsonWebToken $token): void
    {
        if (!$token->verifyRsaSha256Signature($this->publicKey)) {
            throw ApiProblem::unauthorized(
                'Token signature verification failed. The token is likely forged or manipulated.'
            );
        }
    }
}
