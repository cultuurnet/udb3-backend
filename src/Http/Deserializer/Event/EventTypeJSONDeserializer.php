<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Event;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Event\EventType;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class EventTypeJSONDeserializer extends JSONDeserializer
{
    /**
     * @var EventTypeDataValidator
     */
    private $validator;

    public function __construct()
    {
        $assoc = true;
        parent::__construct($assoc);

        $this->validator = new EventTypeDataValidator();
    }

    /**
     * @return EventType
     * @throws DataValidationException
     */
    public function deserialize(StringLiteral $data)
    {
        $data = parent::deserialize($data);
        $this->validator->validate($data);

        return new EventType($data['id'], $data['label']);
    }
}
