<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication\Token;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\InvalidArgumentException;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

abstract class AbstractJsonWebToken extends AbstractToken
{
    private const TIME_LEEWAY = 30;

    /**
     * @var string
     */
    private $jwt;

    /**
     * @var Token
     */
    protected $token;

    /**
     * @throws InvalidArgumentException
     *   If the provided JWT string cannot be parsed
     */
    public function __construct(string $jwt)
    {
        parent::__construct();
        $this->setAuthenticated(false);
        $this->jwt = $jwt;
        $this->token = (new Parser())->parse($jwt);
    }

    public function authenticate(): AbstractJsonWebToken
    {
        $clone = clone $this;
        $clone->setAuthenticated(true);
        return $clone;
    }

    public function hasClaims(array $names): bool
    {
        foreach ($names as $name) {
            if (!$this->token->hasClaim($name)) {
                return false;
            }
        }
        return true;
    }

    public function isUsableAtCurrentTime(): bool
    {
        // Use the built-in validation provided by Lcobucci without any extra validation data.
        // This will automatically validate the time-sensitive claims.
        // Set the leeway to 30 seconds so we can compensate for slight clock skew between auth0 and our own servers.
        // @see https://self-issued.info/docs/draft-ietf-oauth-json-web-token.html#nbfDef
        return $this->token->validate(new ValidationData(null, self::TIME_LEEWAY));
    }

    public function hasValidIssuer(array $validIssuers): bool
    {
        return in_array($this->token->getClaim('iss', ''), $validIssuers, true);
    }

    public function hasAudience(string $audience): bool
    {
        if (!$this->token->hasClaim('aud')) {
            return false;
        }

        // The aud claim can be a string or an array. Convert string to array with one value for consistency.
        $aud = $this->token->getClaim('aud');
        if (is_string($aud)) {
            $aud = [$aud];
        }

        return in_array($audience, $aud, true);
    }

    public function verifyRsaSha256Signature(string $publicKey, ?string $keyPassphrase = null): bool
    {
        $signer = new Sha256();
        $key = new Key($publicKey, $keyPassphrase);
        return $this->token->verify($signer, $key);
    }

    public function getCredentials(): string
    {
        return $this->jwt;
    }
}
