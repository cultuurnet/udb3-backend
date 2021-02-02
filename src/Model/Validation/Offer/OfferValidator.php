<?php

namespace CultuurNet\UDB3\Model\Validation\Offer;

use CultuurNet\UDB3\Model\Validation\Organizer\OrganizerReferenceValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\Audience\AgeRangeValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\Calendar\MultipleSubEventsCalendarValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\Calendar\PeriodicCalendarValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\Calendar\PermanentCalendarValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\Calendar\SingleSubEventCalendarValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\Calendar\StatusValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\ConfigurableEnumValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\Contact\BookingInfoValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\Contact\ContactPointValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\MediaObject\MediaObjectsValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\Moderation\WorkflowStatusValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\Price\PriceInfoValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\Taxonomy\Category\CategoriesValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\Taxonomy\Label\LabelsValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\Text\TranslatedStringValidator;
use CultuurNet\UDB3\Model\Validation\ValueObject\Translation\HasMainLanguageRule;
use CultuurNet\UDB3\Model\Validation\ValueObject\Translation\LanguageValidator;
use Respect\Validation\Rules\Date;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator;

abstract class OfferValidator extends Validator
{
    public function __construct($rules)
    {
        $mandatoryRules = [
            new Key('@id', $this->getIDValidator(), true),
            new Key('mainLanguage', new LanguageValidator(), true),
            new Key('name', new TranslatedStringValidator('name'), true),
            new HasMainLanguageRule('name'),
            new Key('terms', new CategoriesValidator(1), true),
        ];

        $calendarRules = $this->getCalendarRules();

        $optionalRules = [
            new Key('description', new TranslatedStringValidator('description'), false),
            new HasMainLanguageRule('description'),
            new Key('status', new StatusValidator(), false),
            new Key('labels', new LabelsValidator(), false),
            new Key('hiddenLabels', new LabelsValidator(), false),
            new Key('organizer', new OrganizerReferenceValidator(), false),
            new Key('typicalAgeRange', new AgeRangeValidator(), false),
            new Key('contactPoint', new ContactPointValidator(), false),
            new Key('bookingInfo', new BookingInfoValidator(), false),
            new HasMainLanguageRule('bookingInfo.urlLabel'),
            new Key('priceInfo', new PriceInfoValidator(), false),
            new HasMainLanguageRule('priceInfo.[].name'),
            new Key('mediaObject', new MediaObjectsValidator(), false),
            new Key('workflowStatus', new WorkflowStatusValidator(), false),
            new Key('availableFrom', new Date(\DATE_ATOM), false),
        ];

        $allRules = array_merge(
            $mandatoryRules,
            $calendarRules,
            $rules,
            $optionalRules
        );

        parent::__construct($allRules);
    }

    /**
     * @return Validator
     */
    abstract protected function getIDValidator();

    /**
     * @return string[]
     */
    abstract protected function getAllowedCalendarTypes();

    /**
     * @return Validator[]
     */
    private function getCalendarRules()
    {
        $allowedTypes = $this->getAllowedCalendarTypes();

        $availableRules = [
            'single' => new SingleSubEventCalendarValidator(),
            'multiple' => new MultipleSubEventsCalendarValidator(),
            'periodic' => new PeriodicCalendarValidator(),
            'permanent' => new PermanentCalendarValidator(),
        ];

        $rules = array_filter(
            $availableRules,
            function ($type) use ($allowedTypes) {
                return in_array($type, $allowedTypes);
            },
            ARRAY_FILTER_USE_KEY
        );

        array_unshift(
            $rules,
            new Key('calendarType', new ConfigurableEnumValidator($allowedTypes), true)
        );

        return $rules;
    }
}
