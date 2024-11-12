<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;

final class UpdateBookingAvailability implements AuthorizableCommand
{
    private string $itemId;

    private BookingAvailability $bookingAvailability;

    public function __construct(string $offerId, BookingAvailability $bookingAvailability)
    {
        $this->itemId = $offerId;
        $this->bookingAvailability = $bookingAvailability;
    }

    public function getBookingAvailability(): BookingAvailability
    {
        return $this->bookingAvailability;
    }

    public function getItemId(): string
    {
        return $this->itemId;
    }

    public function getPermission(): Permission
    {
        return Permission::aanbodBewerken();
    }
}
