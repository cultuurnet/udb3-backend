<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication;

use CultuurNet\UDB3\Jwt\JwtValidator;
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

    public function __construct(
        JwtValidator $v1JwtValidator,
        JwtValidator $v2JwtValidator
    ) {
        $this->v1JwtValidator = $v1JwtValidator;
        $this->v2JwtValidator = $v2JwtValidator;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof JsonWebToken;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token)
    {
        /* @var JsonWebToken $token */
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

        return new JsonWebToken($udb3Token, true);
    }
}
