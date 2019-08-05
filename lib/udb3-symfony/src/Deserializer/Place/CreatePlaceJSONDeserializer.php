<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\Place;

use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Language;
use ValueObjects\StringLiteral\StringLiteral;

class CreatePlaceJSONDeserializer extends JSONDeserializer
{
    /**
     * @var CreatePlaceDataValidator
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

        $this->validator = new CreatePlaceDataValidator();

        $this->majorInfoJSONDeserializer = new MajorInfoJSONDeserializer();
    }

    /**
     * @param StringLiteral $data
     * @return CreatePlace
     * @throws DataValidationException
     */
    public function deserialize(StringLiteral $data)
    {
        /** @var array $deserializedData */
        $deserializedData = parent::deserialize($data);
        $this->validator->validate($deserializedData);

        $majorInfo = $this->majorInfoJSONDeserializer->deserialize($data);

        return new CreatePlace(
            new Language($deserializedData['mainLanguage']),
            $majorInfo->getTitle(),
            $majorInfo->getType(),
            $majorInfo->getAddress(),
            $majorInfo->getCalendar(),
            $majorInfo->getTheme()
        );
    }
}
