<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Auth\Jwt;

use Psr\Log\LoggerInterface;

final class UitIdV1JwtValidator implements JwtValidator
{
    private JwtValidator $baseValidator;

    private LoggerInterface $logger;

    public function __construct(
        string $publicKey,
        array $validIssuers,
        LoggerInterface $logger
    ) {
        $this->baseValidator = new GenericJwtValidator($publicKey, ['uid'], $validIssuers);
        $this->logger = $logger;
    }

    public function verifySignature(JsonWebToken $token): void
    {
        $this->baseValidator->verifySignature($token);
    }

    public function validateClaims(JsonWebToken $token): void
    {
        $this->baseValidator->validateClaims($token);

        $this->logger->error(
            $token->getUserId() .
            ' has used a v1-token ' .
            'e-mail: ' . ($token->getEmailAddress()?->toString() ?? 'Unknown')
        );
    }
}
