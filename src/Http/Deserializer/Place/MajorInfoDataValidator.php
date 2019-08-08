<?php

namespace CultuurNet\UDB3\Http\Deserializer\Place;

use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\Http\Deserializer\Address\AddressDataValidator;
use CultuurNet\UDB3\Http\Deserializer\Calendar\CalendarForPlaceDataValidator;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\CompositeDataValidator;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\RequiredPropertiesDataValidator;
use CultuurNet\UDB3\Http\Deserializer\Event\EventTypeDataValidator;
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
            ->withValidator(new RequiredPropertiesDataValidator(['name', 'type', 'address', 'calendar']))
            ->withValidator(new AddressDataValidator(), ['address'])
            ->withValidator(new EventTypeDataValidator(), ['type'])
            ->withValidator(new ThemeDataValidator(), ['theme'])
            ->withValidator(new CalendarForPlaceDataValidator(), ['calendar']);
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
