<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\ValidationData;

class JwtDecoderService implements JwtDecoderServiceInterface
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

    /**
     * Used to validate standard time-sensitive claims, i.e. exp should be in the future and nbf and iat should be in
     * the past.
     */
    public function validateTimeSensitiveClaims(Udb3Token $udb3Token): bool
    {
        // Use the built-in validation provided by Lcobucci without any extra validation data.
        // This will automatically validate the time-sensitive claims.
        // Set the leeway to 30 seconds so we can compensate for slight clock skew between auth0 and our own servers.
        // @see https://self-issued.info/docs/draft-ietf-oauth-json-web-token.html#nbfDef
        return $udb3Token->jwtToken()->validate(new ValidationData(null, 30));
    }

    public function validateRequiredClaims(Udb3Token $udb3Token): bool
    {
        foreach ($this->requiredClaims as $claim) {
            if (!$udb3Token->jwtToken()->hasClaim($claim)) {
                return false;
            }
        }

        return true;
    }

    public function validateIssuer(Udb3Token $udb3Token): bool
    {
        $jwt = $udb3Token->jwtToken();

        if (!$jwt->hasClaim('iss')) {
            return false;
        }

        $issuer = $jwt->getClaim('iss');
        return in_array($issuer, $this->validIssuers, true);
    }

    public function verifySignature(Udb3Token $udb3Token): bool
    {
        return $udb3Token->jwtToken()->verify(
            $this->signer,
            $this->publicKey
        );
    }
}
