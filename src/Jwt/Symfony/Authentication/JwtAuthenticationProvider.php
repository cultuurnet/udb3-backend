<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication;

use CultuurNet\UDB3\Jwt\JwtValidator;
use CultuurNet\UDB3\Jwt\Udb3Token;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class JwtAuthenticationProvider implements AuthenticationProviderInterface
{
    /**
     * @var JwtValidator
     */
    private $v1JwtValidator;

    /**
     * @var JwtValidator
     */
    private $v2JwtValidator;

    /**
     * @var string
     */
    private $v2JwtProviderAuth0ClientId;

    public function __construct(
        JwtValidator $v1JwtValidator,
        JwtValidator $v2JwtValidator,
        string $v2JwtValidatorAuth0ClientId
    ) {
        $this->v1JwtValidator = $v1JwtValidator;
        $this->v2JwtValidator = $v2JwtValidator;
        $this->v2JwtProviderAuth0ClientId = $v2JwtValidatorAuth0ClientId;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof JwtUserToken;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token)
    {
        /* @var JwtUserToken $token */
        if (!$this->supports($token)) {
            throw new AuthenticationException(
                'Token type ' . get_class($token) . ' not supported.'
            );
        }

        $udb3Token = $token->getCredentials();

        $validV1Signature = false;
        $validV2Signature = false;

        try {
            $this->v1JwtValidator->verifySignature($udb3Token->jwtToken());
            $validV1Signature = true;
        } catch (AuthenticationException $e) {
            $this->v2JwtValidator->verifySignature($udb3Token->jwtToken());
            $validV2Signature = true;
        }

        if (!$validV1Signature && !$validV2Signature) {
            throw new AuthenticationException(
                'Token signature verification failed. The token is likely forged or manipulated.'
            );
        }

        $validator = $validV1Signature ? $this->v1JwtValidator : $this->v2JwtValidator;

        $validator->validateClaims($udb3Token->jwtToken());

        if ($validV2Signature) {
            $this->validateV2Token($udb3Token);
        }

        return new JwtUserToken($udb3Token, true);
    }

    private function validateV2Token(Udb3Token $jwt): void
    {
        if ($jwt->isAccessToken()) {
            $this->validateV2AccessToken($jwt);
        } else {
            $this->validateV2IdToken($jwt);
        }
    }

    private function validateV2AccessToken(Udb3Token $jwt): void
    {
        if (!$jwt->canUseEntryAPI()) {
            throw new AuthenticationException(
                'The given token and its related client are not allowed to access EntryAPI.',
                403
            );
        }
    }

    private function validateV2IdToken(Udb3Token $jwt): void
    {
        if (!$jwt->audienceContains($this->v2JwtProviderAuth0ClientId)) {
            throw new AuthenticationException(
                'Only legacy id tokens are supported. Please use an access token instead.'
            );
        }
    }
}
