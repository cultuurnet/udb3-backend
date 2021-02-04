<?php

namespace CultuurNet\UDB3\Http\Deserializer\Event;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Http\Deserializer\Calendar\CalendarForEventDataValidator;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\CompositeDataValidator;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\RequiredPropertiesDataValidator;
use CultuurNet\UDB3\Http\Deserializer\Theme\ThemeDataValidator;

class MajorInfoDataValidator implements DataValidatorInterface
{
    /**
     * @var CompositeDataValidator
     */
    private $validator;

    public function __construct()
    {
        $this->validator = (new CompositeDataValidator())
            ->withValidator(new RequiredPropertiesDataValidator(['name', 'type', 'location', 'calendar']))
            ->withValidator(new EventTypeDataValidator(), ['type'])
            ->withValidator(new ThemeDataValidator(), ['theme'])
            ->withValidator(new CalendarForEventDataValidator(), ['calendar']);
    }

    /**
     * @param array $data
     * @throws DataValidationException
     */
    public function validate(array $data)
    {
        $this->validator->validate($data);
    }
}
