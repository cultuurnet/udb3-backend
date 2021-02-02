<?php

namespace CultuurNet\UDB3\Model\Place;

use CultuurNet\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Model\Offer\ImmutableOffer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithOpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

class ImmutablePlace extends ImmutableOffer implements Place
{
    /**
     * @var TranslatedAddress
     */
    private $address;

    /**
     * @var Coordinates|null
     */
    private $coordinates;

    /**
     * @param UUID $id
     * @param Language $mainLanguage
     * @param TranslatedTitle $title
     * @param Calendar $calendar
     * @param TranslatedAddress $address
     * @param Categories $categories
     */
    public function __construct(
        UUID $id,
        Language $mainLanguage,
        TranslatedTitle $title,
        Calendar $calendar,
        TranslatedAddress $address,
        Categories $categories
    ) {
        // Dummy locations generally do not have any categories, so only enforce
        // the requirement of at least one category for non-nil uuids.
        // Normal places require at least one "eventtype" (sic) category.
        // We can not enforce this particular requirement because categories can
        // be POSTed using only their id.
        if ($categories->isEmpty() && !$id->sameAs(self::getDummyLocationId())) {
            throw new \InvalidArgumentException('Categories should not be empty (eventtype required).');
        }

        parent::__construct($id, $mainLanguage, $title, $calendar, $categories);
        $this->address = $address;
    }

    /**
     * @return TranslatedAddress
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param TranslatedAddress $address
     * @return ImmutablePlace
     */
    public function withAddress(TranslatedAddress $address)
    {
        $c = clone $this;
        $c->address = $address;
        return $c;
    }

    /**
     * @return Coordinates|null
     */
    public function getGeoCoordinates()
    {
        return $this->coordinates;
    }

    /**
     * @param Coordinates $coordinates
     * @return ImmutablePlace
     */
    public function withGeoCoordinates(Coordinates $coordinates)
    {
        $c = clone $this;
        $c->coordinates = $coordinates;
        return $c;
    }

    /**
     * @return ImmutablePlace
     */
    public function withoutGeoCoordinates()
    {
        $c = clone $this;
        $c->coordinates = null;
        return $c;
    }

    /**
     * @return bool
     */
    public function isDummyLocation()
    {
        return $this->getId()->sameAs(self::getDummyLocationId());
    }

    /**
     * @param Language $mainLanguage
     * @param TranslatedTitle $title
     * @param TranslatedAddress $address
     * @return ImmutablePlace
     */
    public static function createDummyLocation(
        Language $mainLanguage,
        TranslatedTitle $title,
        TranslatedAddress $address
    ) {
        /** @phpstan-ignore-next-line */
        $place = new static(
            self::getDummyLocationId(),
            $mainLanguage,
            $title,
            new PermanentCalendar(new OpeningHours()),
            $address,
            new Categories()
        );

        return $place;
    }

    /**
     * @return UUID
     */
    public static function getDummyLocationId()
    {
        return new UUID('00000000-0000-0000-0000-000000000000');
    }

    /**
     * @inheritdoc
     */
    protected function guardCalendarType(Calendar $calendar)
    {
        if (!($calendar instanceof CalendarWithOpeningHours)) {
            throw new \InvalidArgumentException('Given calendar should have opening hours.');
        }
    }
}
