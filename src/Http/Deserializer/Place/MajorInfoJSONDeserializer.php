<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Place;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Http\Deserializer\Address\AddressJSONDeserializer;
use CultuurNet\UDB3\Http\Deserializer\Calendar\CalendarForPlaceDataValidator;
use CultuurNet\UDB3\Http\Deserializer\Calendar\CalendarJSONDeserializer;
use CultuurNet\UDB3\Http\Deserializer\Calendar\CalendarJSONParser;
use CultuurNet\UDB3\Http\Deserializer\Event\EventTypeJSONDeserializer;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class MajorInfoJSONDeserializer extends JSONDeserializer
{
    private MajorInfoDataValidator $validator;

    private EventTypeJSONDeserializer $typeDeserializer;

    private AddressJSONDeserializer $addressDeserializer;

    private CalendarJSONDeserializer $calendarDeserializer;

    public function __construct()
    {
        parent::__construct(true);

        $this->validator = new MajorInfoDataValidator();

        $this->typeDeserializer = new EventTypeJSONDeserializer();
        $this->addressDeserializer = new AddressJSONDeserializer();
        $this->calendarDeserializer = new CalendarJSONDeserializer(
            new CalendarJSONParser(),
            new CalendarForPlaceDataValidator()
        );
    }

    /**
     * @throws DataValidationException
     */
    public function deserialize(string $data): MajorInfo
    {
        $data = parent::deserialize($data);
        $this->validator->validate($data);

        $type = $this->typeDeserializer->deserialize(Json::encode($data['type']));

        $address = $this->addressDeserializer->deserialize(Json::encode($data['address']));

        $calendar = $this->calendarDeserializer->deserialize(Json::encode($data['calendar']));

        return new MajorInfo(
            new Title($data['name']),
            $type,
            $address,
            $calendar
        );
    }
}
