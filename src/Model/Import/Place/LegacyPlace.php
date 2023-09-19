<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Place;

use CultuurNet\UDB3\Model\Import\Offer\LegacyOffer;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;

/**
 * @deprecated Should no longer be used because all commands should use the VOs from the Model namespace.
 */
interface LegacyPlace extends LegacyOffer
{
    public function getAddress(): Address;

    /**
     * @return Address[]
     *  Language code as key, and Address as value.
     */
    public function getAddressTranslations(): array;
}
