<?php

namespace CultuurNet\UDB3\Http\Deserializer\ContactPoint;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\ContactPoint;
use ValueObjects\StringLiteral\StringLiteral;

class ContactPointJSONDeserializer extends JSONDeserializer
{
    /**
     * @var ContactPointDataValidator
     */
    private $validator;

    public function __construct()
    {
        $assoc = true;
        parent::__construct($assoc);

        $this->validator = new ContactPointDataValidator();
    }

    /**
     * @param StringLiteral $data
     * @return ContactPoint
     */
    public function deserialize(StringLiteral $data)
    {
        $data = parent::deserialize($data);
        $this->validator->validate($data);

        $phones = [];
        $emails = [];
        $urls = [];

        foreach ($data as $contactPointEntry) {
            switch ($contactPointEntry['type']) {
                case 'phone':
                    $phones[] = $contactPointEntry['value'];
                    break;

                case 'email':
                    $emails[] = $contactPointEntry['value'];
                    break;

                case 'url':
                    $urls[] = $contactPointEntry['value'];
                    break;
            }
        }

        return new ContactPoint($phones, $emails, $urls);
    }
}
