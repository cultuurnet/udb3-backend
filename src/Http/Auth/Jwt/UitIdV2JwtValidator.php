<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Auth\Jwt;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;

final class UitIdV2JwtValidator implements JwtValidator
{
    private JwtValidator $baseValidator;
    private string $v2JwtProviderOAuthClientId;

    public function __construct(string $publicKey, array $validIssuers, string $v2JwtProviderOAuthClientId)
    {
        $this->baseValidator = new GenericJwtValidator($publicKey, ['sub'], $validIssuers);
        $this->v2JwtProviderOAuthClientId = $v2JwtProviderOAuthClientId;
    }

    public function verifySignature(JsonWebToken $token): void
    {
        $this->baseValidator->verifySignature($token);
    }

    public function validateClaims(JsonWebToken $token): void
    {
        $this->baseValidator->validateClaims($token);
        $tokenType = $token->getType();

        if ($tokenType === JsonWebToken::UIT_ID_V2_JWT_PROVIDER_TOKEN) {
            $this->validateIdTokenFromJwtProvider($token);
            return;
        }

        if ($tokenType === JsonWebToken::UIT_ID_V2_USER_ACCESS_TOKEN ||
            $tokenType === JsonWebToken::UIT_ID_V2_CLIENT_ACCESS_TOKEN) {
            $this->validateAccessToken($token);
        }
    }

    private function validateAccessToken(JsonWebToken $jwt): void
    {
        if (!$jwt->hasEntryApiInPubliqApisClaim()) {
            throw ApiProblem::forbidden('The given token and its related client are not allowed to access EntryAPI.');
        }
    }

    private function validateIdTokenFromJwtProvider(JsonWebToken $jwt): void
    {
        // Only accept id tokens if they were provided by the JWT provider v2.
        // If an id token from another 0Auth client is used, ask to use the related access token instead.
        // Don't mention the JWT provider, we don't want to encourage any new usage of it, only support its tokens for
        // backward compatibility in existing integrations (who won't see this error then).
        if (!$jwt->hasAudience($this->v2JwtProviderOAuthClientId)) {
            throw ApiProblem::unauthorized('The given token is an id token. Please use an access token instead.');
        }
    }
}
