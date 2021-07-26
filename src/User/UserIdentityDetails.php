<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User;

use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;

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

    /**
     * @return StringLiteral
     */
    public function getUserId()
    {
        return new StringLiteral($this->userId);
    }

    /**
     * @return StringLiteral
     */
    public function getUserName()
    {
        return new StringLiteral($this->userName);
    }

    /**
     * @return EmailAddress
     */
    public function getEmailAddress()
    {
        return new EmailAddress($this->emailAddress);
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
