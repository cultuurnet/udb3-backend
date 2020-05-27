<?php

namespace CultuurNet\UDB3\Place\Commands;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

final class MarkAsDuplicate implements AuthorizableCommandInterface
{
    /**
     * @var string
     */
    private $duplicatePlaceId;

    /**
     * @var string
     */
    private $canonicalPlaceId;

    public function __construct(string $duplicatePlaceId, string $canonicalPlaceId)
    {
        $this->duplicatePlaceId = $duplicatePlaceId;
        $this->canonicalPlaceId = $canonicalPlaceId;
    }

    public function getDuplicatePlaceId(): string
    {
        return $this->duplicatePlaceId;
    }

    public function getCanonicalPlaceId(): string
    {
        return $this->canonicalPlaceId;
    }

    /**
     * @return string
     */
    public function getItemId()
    {
        return $this->duplicatePlaceId;
    }

    /**
     * @return Permission
     */
    public function getPermission()
    {
        return Permission::GEBRUIKERS_BEHEREN();
    }
}
