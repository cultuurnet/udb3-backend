<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;

final class InMemoryUserEmailAddressRepository implements UserEmailAddressRepository
{
    /**
     * @var EmailAddress[]
     */
    private static array $mappedUserIds = [];

    public static function addUserEmail(string $userId, EmailAddress $emailAddress): void
    {
        self::$mappedUserIds[$userId] = $emailAddress;
    }

    public function getEmailForUserId(string $userId): ?EmailAddress
    {
        if (array_key_exists($userId, self::$mappedUserIds)) {
            return self::$mappedUserIds[$userId];
        }
        return null;
    }
}
