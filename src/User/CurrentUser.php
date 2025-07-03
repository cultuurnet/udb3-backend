<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User;

use RuntimeException;

final class CurrentUser
{
    private static ?array $godUserIds = null;
    private ?string $id;

    public function __construct(?string $id)
    {
        $this->id = $id;
    }

    public static function configureGodUserIds(array $godUserIds): void
    {
        self::$godUserIds = $godUserIds;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function isGodUser(): bool
    {
        if (self::$godUserIds === null) {
            throw new RuntimeException(
                'CurrentUser::configureGodUserIds() must be called before CurrentUser::isGodUser() can be called.'
            );
        }

        return $this->id !== null && in_array($this->id, self::$godUserIds, true);
    }

    public function isAnonymous(): bool
    {
        return $this->id === null;
    }

    public function isMatch(string $userId): bool
    {
        return $userId === $this->id;
    }
}
