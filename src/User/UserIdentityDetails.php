<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User;

class UserIdentityDetails implements \JsonSerializable
{
    private string $userId;

    private string $userName;

    private string $emailAddress;

    public function __construct(
        string $userId,
        string $userName,
        string $emailAddress
    ) {
        $this->userId = $userId;
        $this->userName = $userName;
        $this->emailAddress = $emailAddress;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->userId,
            'email' => $this->emailAddress,
            'username' => $this->userName,
        ];
    }
}
