<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultureFeed_User;
use ValueObjects\StringLiteral\StringLiteral;

class UserIdentification implements UserIdentificationInterface
{
    /**
     * @var string
     */
    private $userId;

    /**
     * @var string[][]
     */
    private $permissionList;

    /**
     * @param string[][] $permissionList
     */
    public function __construct(
        string $userId,
        array $permissionList
    ) {
        $this->userId = $userId;
        $this->permissionList = $permissionList;
    }

    public function isGodUser(): bool
    {
        return in_array(
            $this->userId,
            $this->permissionList['allow_all']
        );
    }

    public function getId(): StringLiteral
    {
        return new StringLiteral($this->userId);
    }
}
