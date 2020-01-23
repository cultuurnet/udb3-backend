<?php declare(strict_types=1);

namespace CultuurNet\UDB3\User;

class Auth0ManagementTokenProvider
{
    /**
     * @var Auth0ManagementTokenGenerator
     */
    private $tokenGenerator;

    /**
     * @var Auth0ManagementTokenRepository
     */
    private $tokenRepository;

    public function __construct(
        Auth0ManagementTokenGenerator $tokenGenerator,
        Auth0ManagementTokenRepository $tokenRepository
    ) {
        $this->tokenGenerator = $tokenGenerator;
        $this->tokenRepository = $tokenRepository;
    }

    public function token(): string
    {
        $token = $this->tokenRepository->token();

        if ($token === null) {
            $token = $this->tokenGenerator->newToken();
            $this->tokenRepository->store($token);
        }

        return $token;
    }
}
