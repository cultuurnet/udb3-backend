<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\Place;

use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Symfony\Deserializer\Address\AddressJSONDeserializer;
use CultuurNet\UDB3\Symfony\Deserializer\Calendar\CalendarForPlaceDataValidator;
use CultuurNet\UDB3\Symfony\Deserializer\Calendar\CalendarJSONDeserializer;
use CultuurNet\UDB3\Symfony\Deserializer\Calendar\CalendarJSONParser;
use CultuurNet\UDB3\Symfony\Deserializer\Event\EventTypeJSONDeserializer;
use CultuurNet\UDB3\Symfony\Deserializer\Theme\ThemeJSONDeserializer;
use CultuurNet\UDB3\Title;
use ValueObjects\StringLiteral\StringLiteral;

class MajorInfoJSONDeserializer extends JSONDeserializer
{
    /**
     * @var MajorInfoDataValidator
     */
    private $validator;

    /**
     * @var EventTypeJSONDeserializer
     */
    private $typeDeserializer;

    /**
     * @var AddressJSONDeserializer
     */
    private $addressDeserializer;

    /**
     * @var CalendarJSONDeserializer
     */
    private $calendarDeserializer;

    /**
     * @var ThemeJSONDeserializer
     */
    private $themeDeserializer;

    public function __construct()
    {
        $assoc = true;
        parent::__construct($assoc);

        $this->validator = new MajorInfoDataValidator();

        $this->typeDeserializer = new EventTypeJSONDeserializer();
        $this->addressDeserializer = new AddressJSONDeserializer();
        $this->calendarDeserializer = new CalendarJSONDeserializer(
            new CalendarJSONParser(),
            new CalendarForPlaceDataValidator()
        );
        $this->themeDeserializer = new ThemeJSONDeserializer();
    }

    /**
     * @param StringLiteral $data
     * @return MajorInfo
     * @throws DataValidationException
     */
    public function deserialize(StringLiteral $data)
    {
        $data = parent::deserialize($data);
        $this->validator->validate($data);

        $type = $this->typeDeserializer->deserialize(
            new StringLiteral(json_encode($data['type']))
        );

        $address = $this->addressDeserializer->deserialize(
            new StringLiteral(json_encode($data['address']))
        );

        $calendar = $this->calendarDeserializer->deserialize(
            new StringLiteral(json_encode($data['calendar']))
        );

        $theme = null;
        if (!empty($data['theme'])) {
            $theme = $this->themeDeserializer->deserialize(
                new StringLiteral(json_encode($data['theme']))
            );
        }

        return new MajorInfo(
            new Title($data['name']),
            $type,
            $address,
            $calendar,
            $theme
        );
    }
}
