<?php

namespace CultuurNet\UDB3\Http\Deserializer\Event;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Http\Deserializer\Calendar\CalendarForEventDataValidator;
use CultuurNet\UDB3\Http\Deserializer\Calendar\CalendarJSONDeserializer;
use CultuurNet\UDB3\Http\Deserializer\Calendar\CalendarJSONParser;
use CultuurNet\UDB3\Http\Deserializer\Theme\ThemeJSONDeserializer;
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
        $this->calendarDeserializer = new CalendarJSONDeserializer(
            new CalendarJSONParser(),
            new CalendarForEventDataValidator()
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

        $calendar = $this->calendarDeserializer->deserialize(
            new StringLiteral(json_encode($data['calendar']))
        );

        $locationId = $data['location'];
        if (is_array($locationId) && isset($locationId['id'])) {
            $locationId = $locationId['id'];
        }
        $locationId = new LocationId($locationId);

        $theme = null;
        if (!empty($data['theme'])) {
            $theme = $this->themeDeserializer->deserialize(
                new StringLiteral(json_encode($data['theme']))
            );
        }

        return new MajorInfo(
            new Title($data['name']),
            $type,
            $locationId,
            $calendar,
            $theme
        );
    }
}
