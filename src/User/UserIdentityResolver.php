<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;

interface UserIdentityResolver
{
    public function getUserById(string $userId): ?UserIdentityDetails;

    public function getUserByEmail(EmailAddress $email): ?UserIdentityDetails;

    public function getUserByNick(string $nick): ?UserIdentityDetails;
}
