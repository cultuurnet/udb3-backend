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
    private $jwtValidator;

    /**
     * @var string
     */
    private $jwtProviderClientId;

    public function __construct(
        JwtValidatorInterface $jwtValidator,
        string $jwtProviderClientId
    ) {
        $this->jwtValidator = $jwtValidator;
        $this->jwtProviderClientId = $jwtProviderClientId;
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

        if (!$this->jwtValidator->verifySignature($jwt)) {
            throw new AuthenticationException(
                'Token signature verification failed. The token is likely forged or manipulated.'
            );
        }

        if (!$this->jwtValidator->validateTimeSensitiveClaims($jwt)) {
            throw new AuthenticationException(
                'Token expired (or not yet usable).'
            );
        }

        if (!$this->jwtValidator->validateRequiredClaims($jwt)) {
            throw new AuthenticationException(
                'Token is missing one of its required claims.'
            );
        }

        if (!$this->jwtValidator->validateIssuer($jwt)) {
            throw new AuthenticationException(
                'Token is not issued by a valid issuer.'
            );
        }

        if ($jwt->isAccessToken()) {
            $this->validateAccessToken($jwt);
        } else {
            $this->validateIdToken($jwt);
        }

        return new JwtUserToken($jwt, true);
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
        if (!$jwt->audienceContains($this->jwtProviderClientId)) {
            throw new AuthenticationException(
                'Only legacy id tokens are supported. Please use an access token instead.'
            );
        }
    }
}
