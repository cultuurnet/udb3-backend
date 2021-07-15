<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication;

use CultuurNet\UDB3\User\UserIdentityDetails;
use InvalidArgumentException;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;

class JsonWebToken extends AbstractJsonWebToken
{
    public const V1_JWT_PROVIDER_TOKEN = 'v1_jwt_provider_token';
    public const V2_JWT_PROVIDER_TOKEN = 'v2_jwt_provider_token';
    public const V2_USER_ACCESS_TOKEN = 'v2_user_access_token';
    public const V2_CLIENT_ACCESS_TOKEN = 'v2_client_access_token';

    /**
     * @return string
     *   One of the V1/V2_..._TOKEN constants.
     */
    public function getType(): string
    {
        // V1 tokens had a non-standardized "uid" claim
        if ($this->token->hasClaim('uid')) {
            return self::V1_JWT_PROVIDER_TOKEN;
        }

        // V2 tokens from the JWT provider are Auth0 ID tokens and do not have an azp claim
        if (!$this->token->hasClaim('azp')) {
            return self::V2_JWT_PROVIDER_TOKEN;
        }

        // V2 client access tokens are always requested using the client-credentials grant type (gty)
        // @see https://stackoverflow.com/questions/49492471/whats-the-meaning-of-the-gty-claim-in-a-jwt-token/49492971
        if ($this->token->getClaim('gty', '') === 'client-credentials') {
            return self::V2_CLIENT_ACCESS_TOKEN;
        }

        // If all other checks fail it's a V2 user access token.
        return self::V2_USER_ACCESS_TOKEN;
    }

    /**
     * Returns the user id used by UDB3 internally.
     * Will either be:
     * - The v1 id for v1 tokens
     * - The v1 id for v2 tokens for a migrated v1 user
     * - The v2 id for v2 tokens for new v2 users
     */
    public function getUserId(): string
    {
        if ($this->token->hasClaim('uid')) {
            return $this->token->getClaim('uid');
        }

        if ($this->token->hasClaim('https://publiq.be/uitidv1id')) {
            return $this->token->getClaim('https://publiq.be/uitidv1id');
        }

        return $this->token->getClaim('sub');
    }

    /**
     * Returns the user id used on the external authorization system.
     * Will either be:
     * - The v1 id for v1 tokens
     * - The v2 id for v2 tokens
     */
    public function getExternalUserId(): string
    {
        if ($this->token->hasClaim('uid')) {
            return $this->token->getClaim('uid');
        }
        return $this->token->getClaim('sub');
    }

    public function containsUserIdentityDetails(): bool
    {
        // Tokens from the V1 JWT provider (= custom) and V2 JWT provider (= Auth0 ID tokens) contain the username and
        // email. Auth0 access tokens do not.
        return $this->hasClaims(['nick', 'email']) || $this->hasClaims(['nickname', 'email']);
    }

    public function getUserIdentityDetails(): ?UserIdentityDetails
    {
        // Tokens from V1 JWT provider (= custom)
        if ($this->hasClaims(['nick', 'email'])) {
            return new UserIdentityDetails(
                new StringLiteral($this->getUserId()),
                new StringLiteral($this->token->getClaim('nick')),
                new EmailAddress($this->token->getClaim('email'))
            );
        }
        // Tokens from V2 JWT provider (= Auth0 ID tokens)
        if ($this->hasClaims(['nickname', 'email'])) {
            return new UserIdentityDetails(
                new StringLiteral($this->getUserId()),
                new StringLiteral($this->token->getClaim('nickname')),
                new EmailAddress($this->token->getClaim('email'))
            );
        }
        return null;
    }

    public function getClientId(): ?string
    {
        if ($this->token === null) {
            return null;
        }

        // Check first if the token has the claim, to prevent an OutOfBoundsException (thrown if the default is set to
        // null and the claim is missing).
        if ($this->token->hasClaim('azp')) {
            return (string) $this->token->getClaim('azp');
        }
        return null;
    }

    public function hasEntryApiInPubliqApisClaim(): bool
    {
        $apis = $this->token->getClaim('https://publiq.be/publiq-apis', '');

        if (!is_string($apis)) {
            return false;
        }

        $apis = explode(' ', $apis);
        return in_array('entry', $apis, true);
    }
}
