<?php

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication;

use CultuurNet\UDB3\Jwt\JwtDecoderServiceInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class JwtAuthenticationProvider implements AuthenticationProviderInterface
{
    /**
     * @var JwtDecoderServiceInterface
     */
    private $decoderService;

    /**
     * @param JwtDecoderServiceInterface $decoderService
     */
    public function __construct(
        JwtDecoderServiceInterface $decoderService
    ) {
        $this->decoderService = $decoderService;
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
                "Token type " . get_class($token) . " not supported."
            );
        }

        $jwt = $token->getCredentials();

        if (!$this->decoderService->verifySignature($jwt)) {
            throw new AuthenticationException(
                "Token signature verification failed. The token is likely forged or manipulated."
            );
        }

        if (!$this->decoderService->validateData($jwt)) {
            throw new AuthenticationException(
                "Token claims validation failed. This most likely means the token is expired."
            );
        }

        if (!$this->decoderService->validateRequiredClaims($jwt)) {
            throw new AuthenticationException(
                "Token is missing one of its required claims."
            );
        }

        return new JwtUserToken($jwt, true);
    }
}
