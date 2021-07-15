<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication\Token;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\Exception\InvalidClaims;
use CultuurNet\UDB3\User\UserIdentityDetails;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;

final class JwtProviderV2Token extends AbstractToken implements Token
{
    public function __construct(string $jwt)
    {
        parent::__construct($jwt);

        if (!$this->hasClaims(['sub', 'nickname', 'email'])) {
            throw new InvalidClaims();
        }
    }

    public function getUserId(): string
    {
        if ($this->token->hasClaim('https://publiq.be/uitidv1id')) {
            return (string) $this->token->getClaim('https://publiq.be/uitidv1id');
        }
        return (string) $this->token->getClaim('sub');
    }

    public function getUserIdentityDetails(): UserIdentityDetails
    {
        return new UserIdentityDetails(
            new StringLiteral($this->getUserId()),
            new StringLiteral((string) $this->token->getClaim('nickname')),
            new EmailAddress((string) $this->token->getClaim('email'))
        );
    }
}
