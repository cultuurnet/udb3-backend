<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebToken;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class JwtBaseValidator implements JwtValidator
{
    /**
     * @var Signer
     */
    private $signer;

    /**
     * @var Key
     */
    private $publicKey;

    /**
     * @var string[]
     */
    private $requiredClaims;

    /**
     * @var string[]
     */
    private $validIssuers;

    /**
     * @param string[] $requiredClaims
     * @param string[] $validIssuers
     */
    public function __construct(
        Signer $signer,
        Key $publicKey,
        array $requiredClaims = [],
        array $validIssuers = []
    ) {
        $this->signer = $signer;
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

    /**
     * Used to validate standard time-sensitive claims, i.e. exp should be in the future and nbf and iat should be in
     * the past.
     */
    private function validateTimeSensitiveClaims(JsonWebToken $token): void
    {
        if (!$token->isUsableAtCurrentTime()) {
            throw new AuthenticationException(
                'Token expired (or not yet usable).'
            );
        }
    }

    private function validateRequiredClaims(JsonWebToken $token): void
    {
        if (!$token->hasClaims($this->requiredClaims)) {
            throw new AuthenticationException(
                'Token is missing one of its required claims.'
            );
        }
    }

    private function validateIssuer(JsonWebToken $token): void
    {
        if (!$token->hasValidIssuer($this->validIssuers)) {
            throw new AuthenticationException(
                'Token is not issued by a valid issuer.'
            );
        }
    }

    public function verifySignature(JsonWebToken $token): void
    {
        $isVerified = $token->getCredentials()->verify(
            $this->signer,
            $this->publicKey
        );

        if (!$isVerified) {
            throw new AuthenticationException(
                'Token signature verification failed. The token is likely forged or manipulated.'
            );
        }
    }
}
