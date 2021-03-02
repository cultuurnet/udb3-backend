<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Management\User;

use CultureFeed_User;
use ValueObjects\StringLiteral\StringLiteral;

class CultureFeedUserIdentification implements UserIdentificationInterface
{
    /**
     * @var CultureFeed_User|null
     */
    private $cultureFeedUser;

    /**
     * @var string[]
     */
    private $permissionList;

    public function __construct(
        ?CultureFeed_User $cultureFeedUser,
        array $permissionList
    ) {
        $this->cultureFeedUser = $cultureFeedUser;
        $this->permissionList = $permissionList;
    }

    public function isGodUser(): bool
    {
        if (!$this->cultureFeedUser) {
            return false;
        }

        return in_array(
            $this->cultureFeedUser->id,
            $this->permissionList['allow_all']
        );
    }

    public function getId(): ?StringLiteral
    {
        if (!$this->cultureFeedUser) {
            return null;
        }

        return new StringLiteral($this->cultureFeedUser->id);
    }
}
