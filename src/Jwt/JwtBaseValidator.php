<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebToken;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\ValidationData;
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
        // Use the built-in validation provided by Lcobucci without any extra validation data.
        // This will automatically validate the time-sensitive claims.
        // Set the leeway to 30 seconds so we can compensate for slight clock skew between auth0 and our own servers.
        // @see https://self-issued.info/docs/draft-ietf-oauth-json-web-token.html#nbfDef
        if (!$token->getCredentials()->validate(new ValidationData(null, 30))) {
            throw new AuthenticationException(
                'Token expired (or not yet usable).'
            );
        }
    }

    private function validateRequiredClaims(JsonWebToken $token): void
    {
        foreach ($this->requiredClaims as $claim) {
            if (!$token->getCredentials()->hasClaim($claim)) {
                throw new AuthenticationException(
                    'Token is missing one of its required claims.'
                );
            }
        }
    }

    private function validateIssuer(JsonWebToken $token): void
    {
        if (!$token->getCredentials()->hasClaim('iss') || !in_array($token->getCredentials()->getClaim('iss'), $this->validIssuers, true)) {
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
