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
        /** @var JsonWebToken $token */
        if (!$this->supports($token)) {
            throw new AuthenticationException(
                'Token type ' . get_class($token) . ' not supported.'
            );
        }

        $isV1 = $token->getType() === JsonWebToken::V1_JWT_PROVIDER_TOKEN;
        $validator = $isV1 ? $this->v1JwtValidator : $this->v2JwtValidator;

        $validator->verifySignature($token);
        $validator->validateClaims($token);

        return $token->authenticate();
    }
}
