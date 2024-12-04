<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Place;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class CreatePlaceJSONDeserializer extends JSONDeserializer
{
    private CreatePlaceDataValidator $validator;

    private MajorInfoJSONDeserializer $majorInfoJSONDeserializer;

    public function __construct()
    {
        parent::__construct(true);

        $this->validator = new CreatePlaceDataValidator();

        $this->majorInfoJSONDeserializer = new MajorInfoJSONDeserializer();
    }

    /**
     * @throws DataValidationException
     */
    public function deserialize(string $data): CreatePlace
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
            $majorInfo->getCalendar()
        );
    }
}
