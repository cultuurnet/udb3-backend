<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebToken;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;

final class InMemoryUserEmailAddressRepository implements UserEmailAddressRepository
{
    private JsonWebToken $token;

    public function __construct(JsonWebToken $token)
    {
        $this->token = $token;
    }

    public function getEmailForUserId(string $userId): ?EmailAddress
    {
        return $this->token->getEmailAddress();
    }
}
