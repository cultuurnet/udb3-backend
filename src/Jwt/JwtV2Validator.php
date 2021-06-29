<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt;

use Lcobucci\JWT\Token;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class JwtV2Validator implements JwtValidator
{
    /**
     * @var JwtValidator
     */
    private $baseValidator;

    /**
     * @var string
     */
    private $v2JwtProviderAuth0ClientId;

    public function __construct(JwtValidator $baseValidator, string $v2JwtProviderAuth0ClientId)
    {
        $this->baseValidator = $baseValidator;
        $this->v2JwtProviderAuth0ClientId = $v2JwtProviderAuth0ClientId;
    }

    public function verifySignature(Token $token): void
    {
        $this->baseValidator->verifySignature($token);
    }

    public function validateClaims(Token $token): void
    {
        $this->baseValidator->validateClaims($token);

        $udb3Token = new Udb3Token($token);
        if ($udb3Token->isAccessToken()) {
            $this->validateAccessToken($udb3Token);
        } else {
            $this->validateIdToken($udb3Token);
        }
    }

    private function validateAccessToken(Udb3Token $jwt): void
    {
        if (!$jwt->canUseEntryAPI()) {
            throw new AuthenticationException(
                'The given token and its related client are not allowed to access EntryAPI.',
                403
            );
        }
    }

    private function validateIdToken(Udb3Token $jwt): void
    {
        if (!$jwt->audienceContains($this->v2JwtProviderAuth0ClientId)) {
            throw new AuthenticationException(
                'Only legacy id tokens are supported. Please use an access token instead.'
            );
        }
    }
}
