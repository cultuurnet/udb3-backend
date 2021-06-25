<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication;

use CultuurNet\UDB3\Jwt\JwtValidatorInterface;
use CultuurNet\UDB3\Jwt\Udb3Token;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class JwtAuthenticationProvider implements AuthenticationProviderInterface
{
    /**
     * @var JwtValidatorInterface
     */
    private $v1JwtValidator;

    /**
     * @var JwtValidatorInterface
     */
    private $v2JwtValidator;

    /**
     * @var string
     */
    private $v2JwtProviderAuth0ClientId;

    public function __construct(
        JwtValidatorInterface $v1JwtValidator,
        JwtValidatorInterface $v2JwtValidator,
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

        $jwt = $token->getCredentials();

        $validV1Signature = $this->v1JwtValidator->verifySignature($jwt);
        $validV2Signature = $this->v2JwtValidator->verifySignature($jwt);

        if (!$validV1Signature && !$validV2Signature) {
            throw new AuthenticationException(
                'Token signature verification failed. The token is likely forged or manipulated.'
            );
        }

        $validator = $validV1Signature ? $this->v1JwtValidator : $this->v2JwtValidator;

        if (!$validator->validateTimeSensitiveClaims($jwt)) {
            throw new AuthenticationException(
                'Token expired (or not yet usable).'
            );
        }

        if (!$validator->validateRequiredClaims($jwt)) {
            throw new AuthenticationException(
                'Token is missing one of its required claims.'
            );
        }

        if (!$validator->validateIssuer($jwt)) {
            throw new AuthenticationException(
                'Token is not issued by a valid issuer.'
            );
        }

        if ($validV2Signature) {
            $this->validateV2Token($jwt);
        }

        return new JwtUserToken($jwt, true);
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
