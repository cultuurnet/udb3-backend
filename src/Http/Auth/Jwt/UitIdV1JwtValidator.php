<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Auth\Jwt;

use Psr\Log\LoggerInterface;

final class UitIdV1JwtValidator implements JwtValidator
{
    private JwtValidator $baseValidator;

    public function __construct(
        string $publicKey,
        array $validIssuers,
        private readonly LoggerInterface $logger
    ) {
        $this->baseValidator = new GenericJwtValidator($publicKey, ['uid'], $validIssuers);
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
