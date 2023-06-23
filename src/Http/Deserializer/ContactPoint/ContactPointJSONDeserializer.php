<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\ContactPoint;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\StringLiteral;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class ContactPointJSONDeserializer extends JSONDeserializer
{
    private ContactPointDataValidator $validator;

    public function __construct()
    {
        parent::__construct(true);

        $this->validator = new ContactPointDataValidator();
    }

    public function deserialize(StringLiteral $data): ContactPoint
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
