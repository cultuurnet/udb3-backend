<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User;

use Lcobucci\JWT\Parser;

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

    /**
     * @var Parser
     */
    private $parser;

    public function __construct(
        Auth0ManagementTokenGenerator $tokenGenerator,
        Auth0ManagementTokenRepository $tokenRepository,
        Parser $parser
    ) {
        $this->tokenGenerator = $tokenGenerator;
        $this->tokenRepository = $tokenRepository;
        $this->parser = $parser;
    }

    public function token(): string
    {
        $token = $this->tokenRepository->token();

        if ($token === null || $this->isExpired($token)) {
            $token = $this->tokenGenerator->newToken();
            $this->tokenRepository->store($token);
        }

        return $token;
    }

    private function isExpired(string $token): bool
    {
        $parsed = $this->parser->parse($token);
        return $parsed->isExpired();
    }
}
