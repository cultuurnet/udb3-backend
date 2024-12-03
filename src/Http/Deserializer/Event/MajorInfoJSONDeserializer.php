<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Event;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Http\Deserializer\Calendar\CalendarForEventDataValidator;
use CultuurNet\UDB3\Http\Deserializer\Calendar\CalendarJSONDeserializer;
use CultuurNet\UDB3\Http\Deserializer\Calendar\CalendarJSONParser;
use CultuurNet\UDB3\Http\Deserializer\Theme\ThemeJSONDeserializer;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Category\CategoryDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class MajorInfoJSONDeserializer extends JSONDeserializer
{
    private MajorInfoDataValidator $validator;

    private CalendarJSONDeserializer $calendarDeserializer;

    private ThemeJSONDeserializer $themeDeserializer;

    public function __construct()
    {
        parent::__construct(true);

        $this->validator = new MajorInfoDataValidator();

        $this->calendarDeserializer = new CalendarJSONDeserializer(
            new CalendarJSONParser(),
            new CalendarForEventDataValidator()
        );
        $this->themeDeserializer = new ThemeJSONDeserializer();
    }

    /**
     * @throws DataValidationException
     */
    public function deserialize(string $data): MajorInfo
    {
        $data = parent::deserialize($data);
        $this->validator->validate($data);

        $type = (new CategoryDenormalizer(CategoryDomain::eventType()))->denormalize($data['type'], Category::class);

        $calendar = $this->calendarDeserializer->deserialize(Json::encode($data['calendar']));

        $locationId = $data['location'];
        if (is_array($locationId) && isset($locationId['id'])) {
            $locationId = $locationId['id'];
        }
        $locationId = new LocationId($locationId);

        $theme = null;
        if (!empty($data['theme'])) {
            $theme = $this->themeDeserializer->deserialize(Json::encode($data['theme']));
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
