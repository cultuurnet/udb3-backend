<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Auth\Jwt;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use DateInterval;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;

final class JsonWebToken
{
    public const UIT_ID_V1_JWT_PROVIDER_TOKEN = 'uit_v1_jwt_provider_token';
    public const UIT_ID_V2_JWT_PROVIDER_TOKEN = 'uit_v2_jwt_provider_token';
    public const UIT_ID_V2_USER_ACCESS_TOKEN = 'uit_v2_user_access_token';
    public const UIT_ID_V2_CLIENT_ACCESS_TOKEN = 'uit_v2_client_access_token';

    private const TIME_LEEWAY = 30;

    private string $jwt;

    private UnencryptedToken $token;

    /**
     * @throws InvalidArgumentException
     *   If the provided JWT string cannot be parsed
     */
    public function __construct(string $jwt)
    {
        $this->jwt = $jwt;

        /** @var UnencryptedToken $token */
        $token = (new Parser(new JoseEncoder()))->parse($jwt);
        $this->token = $token;
    }

    /**
     * @return string
     *   One of the UIT_ID_..._TOKEN constants.
     */
    public function getType(): string
    {
        // V1 tokens had a non-standardized "uid" claim
        if ($this->token->claims()->has('uid')) {
            return self::UIT_ID_V1_JWT_PROVIDER_TOKEN;
        }

        // V2 tokens from the JWT provider are Auth0 ID tokens and do not have an azp claim
        if (!$this->token->claims()->has('azp')) {
            return self::UIT_ID_V2_JWT_PROVIDER_TOKEN;
        }

        // This is a check for Keycloak tokens, auth0 does not have this field
        if ($this->token->claims()->get('typ', '') === 'ID') {
            return self::UIT_ID_V2_JWT_PROVIDER_TOKEN;
        }

        // V2 client access tokens are always requested using the client-credentials grant type (gty)
        // @see https://stackoverflow.com/questions/49492471/whats-the-meaning-of-the-gty-claim-in-a-jwt-token/49492971
        if ($this->token->claims()->get('gty', '') === 'client-credentials') {
            return self::UIT_ID_V2_CLIENT_ACCESS_TOKEN;
        }

        // If all other checks fail it's a V2 user access token.
        return self::UIT_ID_V2_USER_ACCESS_TOKEN;
    }

    public function getUserId(): string
    {
        if ($this->token->claims()->has('uid')) {
            return $this->token->claims()->get('uid');
        }

        if ($this->token->claims()->has('https://publiq.be/uitidv1id')) {
            return $this->token->claims()->get('https://publiq.be/uitidv1id');
        }

        if ($this->getType() === self::UIT_ID_V2_CLIENT_ACCESS_TOKEN && $this->token->claims()->has('azp')) {
            return $this->token->claims()->get('azp') . '@clients';
        }

        return $this->token->claims()->get('sub');
    }

    public function getUserIdentityDetails(UserIdentityResolver $userIdentityResolver): ?UserIdentityDetails
    {
        if ($this->getType() === self::UIT_ID_V2_CLIENT_ACCESS_TOKEN) {
            return null;
        }

        // Tokens from V1 JWT provider (= custom)
        if ($this->hasClaims(['nick', 'email'])) {
            return new UserIdentityDetails(
                $this->getUserId(),
                $this->token->claims()->get('nick'),
                $this->token->claims()->get('email')
            );
        }

        // Tokens from V2 JWT provider (= Auth0 ID tokens)
        if ($this->hasClaims(['nickname', 'email'])) {
            return new UserIdentityDetails(
                $this->getUserId(),
                $this->token->claims()->get('nickname'),
                $this->token->claims()->get('email')
            );
        }

        try {
            return $userIdentityResolver->getUserById($this->getUserId());
        } catch (Exception $e) {
            return null;
        }
    }

    public function getEmailAddress(): ?EmailAddress
    {
        if ($this->token->claims()->has('email')) {
            return new EmailAddress($this->token->claims()->get('email'));
        }
        if ($this->token->claims()->has('https://publiq.be/email')) {
            return new EmailAddress($this->token->claims()->get('https://publiq.be/email'));
        }
        return null;
    }

    public function getClientId(): ?string
    {
        // Check first if the token has the claim, to prevent an OutOfBoundsException (thrown if the default is set to
        // null and the claim is missing).
        if ($this->token->claims()->has('azp')) {
            return (string) $this->token->claims()->get('azp');
        }
        return null;
    }

    public function getClientName(): ?string
    {
        // Check first if the token has the claim, to prevent an OutOfBoundsException (thrown if the default is set to
        // null and the claim is missing).
        if ($this->token->claims()->has('https://publiq.be/client-name')) {
            return (string) $this->token->claims()->get('https://publiq.be/client-name');
        }
        return null;
    }

    public function hasClaims(array $names): bool
    {
        foreach ($names as $name) {
            if (!$this->token->claims()->has($name)) {
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
        return (new Validator())->validate(
            $this->token,
            new LooseValidAt(
                new SystemClock(new DateTimeZone('CET')),
                new DateInterval('PT' . self::TIME_LEEWAY . 'S')
            )
        );
    }

    public function hasValidIssuer(array $validIssuers): bool
    {
        return in_array($this->token->claims()->get('iss', ''), $validIssuers, true);
    }

    public function hasAudience(string $audience): bool
    {
        if (!$this->token->claims()->has('aud')) {
            return false;
        }

        // The aud claim can be a string or an array. Convert string to array with one value for consistency.
        $aud = $this->token->claims()->get('aud');
        if (is_string($aud)) {
            $aud = [$aud];
        }

        return in_array($audience, $aud, true);
    }

    public function hasEntryApiInPubliqApisClaim(): bool
    {
        $apis = $this->token->claims()->get('https://publiq.be/publiq-apis', '');

        if (!is_string($apis)) {
            return false;
        }

        $apis = explode(' ', $apis);
        return in_array('entry', $apis, true);
    }

    public function verifyRsaSha256Signature(string $publicKey, ?string $keyPassphrase = ''): bool
    {
        if (empty($publicKey)) {
            return false;
        }

        return (new Validator())->validate(
            $this->token,
            new SignedWith(new Sha256(), InMemory::plainText($publicKey, $keyPassphrase))
        );
    }

    public function getCredentials(): string
    {
        return $this->jwt;
    }
}
