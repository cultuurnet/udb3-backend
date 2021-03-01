<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\Event;

use CultuurNet\UDB3\Model\Validation\Offer\OfferValidator;
use CultuurNet\UDB3\Model\Validation\Place\PlaceReferenceValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\Audience\AudienceTypeValidator;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use Respect\Validation\Rules\Key;
use Respect\Validation\Rules\KeyNested;
use Respect\Validation\Validator;

class EventValidator extends OfferValidator
{
    /**
     * @param Validator[] $extraRules
     */
    public function __construct(array $extraRules = [])
    {
        $rules = [
            new Key('location', new PlaceReferenceValidator(), true),
            new KeyNested('audience.audienceType', new AudienceTypeValidator(), false),
        ];

        $rules = array_merge($rules, $extraRules);

        parent::__construct($rules);
    }

    /**
     * @inheritdoc
     */
    protected function getIDValidator()
    {
        return new EventIDValidator();
    }

    /**
     * @return string[]
     */
    protected function getAllowedCalendarTypes()
    {
        return CalendarType::getAllowedValues();
    }
}
