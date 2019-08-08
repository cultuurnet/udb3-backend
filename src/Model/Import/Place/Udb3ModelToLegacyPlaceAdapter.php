<?php

namespace CultuurNet\UDB3\Model\Import\Place;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Model\Place\Place;
use CultuurNet\UDB3\Model\Import\Offer\Udb3ModelToLegacyOfferAdapter;

class Udb3ModelToLegacyPlaceAdapter extends Udb3ModelToLegacyOfferAdapter implements LegacyPlace
{
    /**
     * @var Place
     */
    private $place;

    /**
     * @param Place $place
     */
    public function __construct(Place $place)
    {
        parent::__construct($place);
        $this->place = $place;
    }

    /**
     * @inheritdoc
     */
    public function getAddress()
    {
        $address = $this->place->getAddress();

        return Address::fromUdb3ModelAddress(
            $address->getTranslation(
                $address->getOriginalLanguage()
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function getAddressTranslations()
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
