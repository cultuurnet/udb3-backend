<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User;

class UserIdentityDetails implements \JsonSerializable
{
    /**
     * @var string
     */
    private $userId;

    /**
     * @var string
     */
    private $userName;

    /**
     * @var string
     */
    private $emailAddress;

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

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'uuid' => $this->userId,
            'email' => $this->emailAddress,
            'username' => $this->userName,
        ];
    }
}
