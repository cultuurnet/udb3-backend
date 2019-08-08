<?php

namespace CultuurNet\UDB3\Http\Deserializer\Event;

use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Event\EventType;
use ValueObjects\StringLiteral\StringLiteral;

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
     * @param StringLiteral $data
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
