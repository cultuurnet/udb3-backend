<?php

namespace CultuurNet\UDB3\User;

use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;

class UserIdentityDetails implements \JsonSerializable
{
    public const SYSTEM_USER_UUID = '00000000-0000-0000-0000-000000000000';

    /**
     * @var StringLiteral
     */
    private $userId;

    /**
     * @var StringLiteral
     */
    private $userName;

    /**
     * @var EmailAddress
     */
    private $emailAddress;


    public function __construct(
        StringLiteral $userId,
        StringLiteral $userName,
        EmailAddress $emailAddress
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
        return $this->userId;
    }

    /**
     * @return StringLiteral
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @return EmailAddress
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'uuid' => $this->userId->toNative(),
            'email' => $this->emailAddress->toNative(),
            'username' => $this->userName->toNative(),
        ];
    }
}
