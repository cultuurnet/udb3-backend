<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User;

use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;

interface UserIdentityResolver
{
    public function getUserById(StringLiteral $userId): ?UserIdentityDetails;

    public function getUserByEmail(EmailAddress $email): ?UserIdentityDetails;

    public function getUserByNick(StringLiteral $nick): ?UserIdentityDetails;
}
