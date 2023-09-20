<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Event;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Event\EventType;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class EventTypeJSONDeserializer extends JSONDeserializer
{
    private EventTypeDataValidator $validator;

    public function __construct()
    {
        parent::__construct(true);

        $this->validator = new EventTypeDataValidator();
    }

    /**
     * @throws DataValidationException
     */
    public function deserialize(string $data): EventType
    {
        $data = parent::deserialize($data);
        $this->validator->validate($data);

        return new EventType($data['id'], $data['label']);
    }
}
