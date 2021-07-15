<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication\Token;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\Exception\InvalidClaims;
use CultuurNet\UDB3\User\UserIdentityDetails;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;

final class JwtProviderV1Token extends AbstractToken implements Token
{
    public function __construct(string $jwt)
    {
        parent::__construct($jwt);

        if (!$this->hasClaims(['uid', 'nick', 'email'])) {
            throw new InvalidClaims();
        }
    }

    public function getUserId(): string
    {
        return (string) $this->token->getClaim('uid');
    }

    public function getUserIdentityDetails(): UserIdentityDetails
    {
        return new UserIdentityDetails(
            new StringLiteral($this->getUserId()),
            new StringLiteral((string) $this->token->getClaim('nick')),
            new EmailAddress((string) $this->token->getClaim('email'))
        );
    }
}
