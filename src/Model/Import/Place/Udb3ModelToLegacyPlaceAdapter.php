<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Place;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Model\Place\Place;
use CultuurNet\UDB3\Model\Import\Offer\Udb3ModelToLegacyOfferAdapter;

/**
 * @deprecated Should no longer be used because all commands should use the VOs from the Model namespace.
 */
class Udb3ModelToLegacyPlaceAdapter extends Udb3ModelToLegacyOfferAdapter implements LegacyPlace
{
    private Place $place;

    public function __construct(Place $place)
    {
        $this->place = $place;
    }

    public function getAddressTranslations(): array
    {
        $translatedAddress = $this->place->getAddress();
        $addresses = [];

        foreach ($translatedAddress->getLanguagesWithoutOriginal() as $language) {
            $addresses[$language->toString()] = Address::fromUdb3ModelAddress(
                $translatedAddress->getTranslation($language)
            );
        }

        return $addresses;
    }
}
