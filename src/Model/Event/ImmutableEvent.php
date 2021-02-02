<?php

namespace CultuurNet\UDB3\Model\Event;

use CultuurNet\UDB3\Model\Offer\ImmutableOffer;
use CultuurNet\UDB3\Model\Place\PlaceReference;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

class ImmutableEvent extends ImmutableOffer implements Event
{
    /**
     * @var PlaceReference
     */
    private $placeReference;

    /**
     * @var AudienceType
     */
    private $audience;

    /**
     * @param UUID $id
     * @param Language $mainLanguage
     * @param TranslatedTitle $title
     * @param Calendar $calendar
     * @param PlaceReference $placeReference
     * @param Categories $categories
     */
    public function __construct(
        UUID $id,
        Language $mainLanguage,
        TranslatedTitle $title,
        Calendar $calendar,
        PlaceReference $placeReference,
        Categories $categories
    ) {
        // Do not enforce this on Categories class itself because it can cause
        // problems with other models, eg. Place (because dummy locations have
        // no categories).
        // We can not enforce the exact requirement that "eventtype" is required
        // because categories can be POSTed using only their id.
        if ($categories->isEmpty()) {
            throw new \InvalidArgumentException('Categories should not be empty (eventtype required).');
        }

        parent::__construct($id, $mainLanguage, $title, $calendar, $categories);
        $this->placeReference = $placeReference;
        $this->audience = AudienceType::everyone();
    }

    /**
     * @return PlaceReference
     */
    public function getPlaceReference()
    {
        return $this->placeReference;
    }

    /**
     * @param PlaceReference $placeReference
     * @return ImmutableEvent
     */
    public function withPlaceReference(PlaceReference $placeReference)
    {
        $c = clone $this;
        $c->placeReference = $placeReference;
        return $c;
    }

    /**
     * @inheritdoc
     */
    public function getAudienceType()
    {
        return $this->audience;
    }

    /**
     * @param AudienceType $audience
     * @return ImmutableEvent
     */
    public function withAudienceType(AudienceType $audience)
    {
        $c = clone $this;
        $c->audience = $audience;
        return $c;
    }

    /**
     * @inheritdoc
     */
    protected function guardCalendarType(Calendar $calendar)
    {
        // Any calendar is fine for events.
        return;
    }
}
