<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;

interface UserEmailAddressRepository
{
    public function getEmailForUserId(string $userId): ?EmailAddress;
}
