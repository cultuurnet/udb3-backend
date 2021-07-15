<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\Token;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class JwtBaseValidator implements JwtValidator
{
    /**
     * @var string
     */
    private $publicKey;

    /**
     * @var string[]
     */
    private $validIssuers;

    /**
     * @param string[] $validIssuers
     */
    public function __construct(
        string $publicKey,
        array $validIssuers = []
    ) {
        $this->publicKey = $publicKey;
        $this->validIssuers = $validIssuers;

        if (count($validIssuers) !== count(array_filter($this->validIssuers, 'is_string'))) {
            throw new \InvalidArgumentException(
                'All valid issuers should be strings.'
            );
        }
    }

    public function validateClaims(Token $token): void
    {
        $this->validateTimeSensitiveClaims($token);
        $this->validateIssuer($token);
    }

    private function validateTimeSensitiveClaims(Token $token): void
    {
        if (!$token->isUsableAtCurrentTime()) {
            throw new AuthenticationException(
                'Token expired (or not yet usable).'
            );
        }
    }

    private function validateIssuer(Token $token): void
    {
        if (!$token->hasValidIssuer($this->validIssuers)) {
            throw new AuthenticationException(
                'Token is not issued by a valid issuer.'
            );
        }
    }

    public function verifySignature(Token $token): void
    {
        if (!$token->verifyRsaSha256Signature($this->publicKey)) {
            throw new AuthenticationException(
                'Token signature verification failed. The token is likely forged or manipulated.'
            );
        }
    }
}
