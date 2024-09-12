<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Place;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Http\Deserializer\Address\AddressDataValidator;
use CultuurNet\UDB3\Http\Deserializer\Calendar\CalendarForPlaceDataValidator;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\CompositeDataValidator;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\RequiredPropertiesDataValidator;
use CultuurNet\UDB3\Http\Deserializer\Event\EventTypeDataValidator;
use CultuurNet\UDB3\Http\Deserializer\Theme\ThemeDataValidator;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class MajorInfoDataValidator implements DataValidatorInterface
{
    private CompositeDataValidator $validator;

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
     * @throws DataValidationException
     */
    public function validate(array $data): void
    {
        $this->validator->validate($data);
    }
}
