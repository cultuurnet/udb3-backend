<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Place;

use CultuurNet\UDB3\Model\Place\Place;
use CultuurNet\UDB3\Model\Import\Offer\Udb3ModelToLegacyOfferAdapter;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;

/**
 * @deprecated Should no longer be used because all commands should use the VOs from the Model namespace.
 */
class Udb3ModelToLegacyPlaceAdapter extends Udb3ModelToLegacyOfferAdapter implements LegacyPlace
{
    private Place $place;

    public function __construct(Place $place)
    {
        parent::__construct($place);
        $this->place = $place;
    }

    public function getAddress(): Address
    {
        return $this->place->getAddress()->getTranslation(
            $this->place->getAddress()->getOriginalLanguage()
        );
    }

    public function getAddressTranslations(): array
    {
        $translatedAddress = $this->place->getAddress();
        $addresses = [];

        foreach ($translatedAddress->getLanguagesWithoutOriginal() as $language) {
            $addresses[$language->toString()] = $this->place->getAddress()->getTranslation($language);
        }

        return $addresses;
    }
}
