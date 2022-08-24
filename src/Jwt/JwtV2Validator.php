<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebToken;

final class JwtV2Validator implements JwtValidator
{
    private JwtValidator $baseValidator;
    private string $v2JwtProviderAuth0ClientId;

    public function __construct(JwtValidator $baseValidator, string $v2JwtProviderAuth0ClientId)
    {
        $this->baseValidator = $baseValidator;
        $this->v2JwtProviderAuth0ClientId = $v2JwtProviderAuth0ClientId;
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
        // If an id token from another Auth0 client is used, ask to use the related access token instead.
        // Don't mention the JWT provider, we don't want to encourage any new usage of it, only support its tokens for
        // backward compatibility in existing integrations (who won't see this error then).
        if (!$jwt->hasAudience($this->v2JwtProviderAuth0ClientId)) {
            throw ApiProblem::unauthorized('The given token is an id token. Please use an access token instead.');
        }
    }
}
