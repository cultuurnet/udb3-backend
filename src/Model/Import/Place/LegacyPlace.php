<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Place;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Model\Import\Offer\LegacyOffer;

/**
 * @deprecated Should no longer be used because all commands should use the VOs from the Model namespace.
 */
interface LegacyPlace extends LegacyOffer
{
    /**
     * @return Address[]
     *  Language code as key, and Address as value.
     */
    public function getAddressTranslations(): array;
}
