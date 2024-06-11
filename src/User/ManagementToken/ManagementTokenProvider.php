<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\ManagementToken;

use DateTime;

class ManagementTokenProvider
{
    private ManagementTokenGenerator $tokenGenerator;

    private TokenRepository $tokenRepository;

    public function __construct(
        ManagementTokenGenerator $tokenGenerator,
        TokenRepository $tokenRepository
    ) {
        $this->tokenGenerator = $tokenGenerator;
        $this->tokenRepository = $tokenRepository;
    }

    public function token(): string
    {
        $token = $this->tokenRepository->token();

        if ($token === null || $this->expiresWithin($token, '+5 minutes')) {
            $token = $this->tokenGenerator->newToken();
            $this->tokenRepository->store($token);
        }

        return $token->getToken();
    }

    private function expiresWithin(ManagementToken $token, string $offset): bool
    {
        return (new DateTime())->modify($offset) > $token->getExpiresAt();
    }
}
