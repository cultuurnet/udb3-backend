<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Event;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class CreateEventJSONDeserializer extends JSONDeserializer
{
    private CreateEventDataValidator $validator;

    private MajorInfoJSONDeserializer $majorInfoJSONDeserializer;

    public function __construct()
    {
        parent::__construct(true);

        $this->validator = new CreateEventDataValidator();

        $this->majorInfoJSONDeserializer = new MajorInfoJSONDeserializer();
    }

    /**
     * @throws DataValidationException
     */
    public function deserialize(string $data): CreateEvent
    {
        /** @var array $deserializedData */
        $deserializedData = parent::deserialize($data);
        $this->validator->validate($deserializedData);

        $majorInfo = $this->majorInfoJSONDeserializer->deserialize($data);

        return new CreateEvent(
            new Language($deserializedData['mainLanguage']),
            $majorInfo->getTitle(),
            EventType::fromUdb3ModelCategory($majorInfo->getType()),
            $majorInfo->getLocation(),
            $majorInfo->getCalendar(),
            $majorInfo->getTheme()
        );
    }
}
