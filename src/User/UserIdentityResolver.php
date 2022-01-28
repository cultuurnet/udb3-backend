<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use ValueObjects\StringLiteral\StringLiteral;

interface UserIdentityResolver
{
    public function getUserById(StringLiteral $userId): ?UserIdentityDetails;

    public function getUserByEmail(EmailAddress $email): ?UserIdentityDetails;

    public function getUserByNick(StringLiteral $nick): ?UserIdentityDetails;
}
