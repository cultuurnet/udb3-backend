<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Place;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Model\Offer\ImmutableOffer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithOpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use InvalidArgumentException;

final class ImmutablePlace extends ImmutableOffer implements Place
{
    private TranslatedAddress $address;

    private ?Coordinates $coordinates = null;

    public function __construct(
        Uuid $id,
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
        if ($categories->isEmpty() && !$id->sameAs(self::getNilLocationId())) {
            throw new InvalidArgumentException('Categories should not be empty (eventtype required).');
        }

        parent::__construct($id, $mainLanguage, $title, $calendar, $categories);
        $this->address = $address;
    }

    public function getAddress(): TranslatedAddress
    {
        return $this->address;
    }

    public function withAddress(TranslatedAddress $address): ImmutablePlace
    {
        $c = clone $this;
        $c->address = $address;
        return $c;
    }

    public function getGeoCoordinates(): ?Coordinates
    {
        return $this->coordinates;
    }

    public function withGeoCoordinates(Coordinates $coordinates): ImmutablePlace
    {
        $c = clone $this;
        $c->coordinates = $coordinates;
        return $c;
    }

    public function withoutGeoCoordinates(): ImmutablePlace
    {
        $c = clone $this;
        $c->coordinates = null;
        return $c;
    }

    public function isNilLocation(): bool
    {
        return $this->getId()->sameAs(self::getNilLocationId());
    }

    private static function createDummyLocation(
        Language $mainLanguage,
        TranslatedTitle $title,
        TranslatedAddress $address,
        Categories $categories = null
    ): ImmutablePlace {
        return new ImmutablePlace(
            self::getNilLocationId(),
            $mainLanguage,
            $title,
            new PermanentCalendar(new OpeningHours()),
            $address,
            $categories ?? new Categories()
        );
    }

    public static function createNilLocation(): ImmutablePlace
    {
        return self::createDummyLocation(
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title('Online')
            ),
            new TranslatedAddress(
                new Language('nl'),
                new Address(
                    new Street('___'),
                    new PostalCode('0000'),
                    new Locality('___'),
                    new CountryCode('BE')
                )
            ),
            new Categories(
                new Category(
                    new CategoryID('0.8.0.0.0'),
                    new CategoryLabel('Openbare ruimte'),
                    new CategoryDomain('eventtype')
                )
            )
        );
    }

    public static function getNilLocationId(): Uuid
    {
        return new Uuid(Uuid::NIL);
    }

    protected function guardCalendarType(Calendar $calendar): void
    {
        if (!($calendar instanceof CalendarWithOpeningHours)) {
            throw new InvalidArgumentException('Given calendar should have opening hours.');
        }
    }
}
