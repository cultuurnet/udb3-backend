<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use Lcobucci\JWT\Token;

/**
 * A wrapper class around the current jwt token to hide away the complexity of determining the correct id based on
 * multiple claims etc.
 */
final class Udb3Token
{
    private const CLIENTS = '@clients';

    /**
     * @var Token
     */
    private $token;

    public function __construct(Token $token)
    {
        $this->token = $token;
    }

    public function id(): string
    {
        if ($this->token->hasClaim('uid')) {
            return $this->token->getClaim('uid');
        }

        if ($this->token->hasClaim('https://publiq.be/uitidv1id')) {
            return $this->token->getClaim('https://publiq.be/uitidv1id');
        }

        if ($this->endsWith($this->token->getClaim('sub'), self::CLIENTS)) {
            return 'client|' . rtrim($this->token->getClaim('sub'), self::CLIENTS);
        }
        return $this->token->getClaim('sub');
    }

    public function jwtToken(): Token
    {
        return $this->token;
    }

    public function isAccessToken(): bool
    {
        // This does not 100% guarantee that the token is an access token, because an access token does not have an azp
        // if it has no specific aud. However we require our integrators to always include the "https://api.publiq.be"
        // aud, so access tokens should always have an azp in our case.
        if ($this->getClientId() === null) {
            return false;
        }

        if (!$this->token->hasClaim('sub')) {
            return true;
        }

        return !$this->endsWith($this->token->getClaim('sub'), self::CLIENTS);
    }

    public function isClientToken(): bool
    {
        if ($this->getClientId() === null) {
            return false;
        }

        if (!$this->token->hasClaim('sub')) {
            return false;
        }

        return $this->endsWith($this->token->getClaim('sub'), self::CLIENTS);
    }

    public function getClientId(): ?string
    {
        // Check first if the token has the claim, to prevent an OutOfBoundsException (thrown if the default is set to
        // null and the claim is missing).
        if ($this->token->hasClaim('azp')) {
            return (string) $this->token->getClaim('azp');
        }
        return null;
    }

    public function audienceContains(string $audience): bool
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

    public function canUseEntryAPI(): bool
    {
        $apis = $this->token->getClaim('https://publiq.be/publiq-apis', '');

        if (!is_string($apis)) {
            return false;
        }

        $apis = explode(' ', $apis);
        return in_array('entry', $apis, true);
    }

    private function endsWith(string $haystack, string $needle): bool
    {
        return substr($haystack, -strlen($needle)) === $needle;
    }
}
