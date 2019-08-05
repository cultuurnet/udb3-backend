<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\Event;

use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Language;
use ValueObjects\StringLiteral\StringLiteral;

class CreateEventJSONDeserializer extends JSONDeserializer
{
    /**
     * @var CreateEventDataValidator
     */
    private $validator;

    /**
     * @var MajorInfoJSONDeserializer
     */
    private $majorInfoJSONDeserializer;

    public function __construct()
    {
        $assoc = true;
        parent::__construct($assoc);

        $this->validator = new CreateEventDataValidator();

        $this->majorInfoJSONDeserializer = new MajorInfoJSONDeserializer();
    }

    /**
     * @param StringLiteral $data
     * @return CreateEvent
     * @throws DataValidationException
     */
    public function deserialize(StringLiteral $data)
    {
        /** @var array $deserializedData */
        $deserializedData = parent::deserialize($data);
        $this->validator->validate($deserializedData);

        $majorInfo = $this->majorInfoJSONDeserializer->deserialize($data);

        return new CreateEvent(
            new Language($deserializedData['mainLanguage']),
            $majorInfo->getTitle(),
            $majorInfo->getType(),
            $majorInfo->getLocation(),
            $majorInfo->getCalendar(),
            $majorInfo->getTheme()
        );
    }
}
