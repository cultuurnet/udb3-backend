<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\Organizer;

use CultuurNet\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Symfony\Deserializer\Address\AddressJSONDeserializer;
use CultuurNet\UDB3\Symfony\Deserializer\ContactPoint\ContactPointJSONDeserializer;
use CultuurNet\UDB3\Title;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class OrganizerCreationPayloadJSONDeserializer extends JSONDeserializer
{
    /**
     * @var OrganizerCreationPayloadDataValidator
     */
    private $validator;

    /**
     * @var AddressJSONDeserializer
     */
    private $addressDeserializer;

    /**
     * @var ContactPointJSONDeserializer
     */
    private $contactPointDeserializer;

    public function __construct()
    {
        $assoc = true;
        parent::__construct($assoc);

        $this->validator = new OrganizerCreationPayloadDataValidator();

        $this->addressDeserializer = new AddressJSONDeserializer();
        $this->contactPointDeserializer = new ContactPointJSONDeserializer();
    }

    /**
     * @param StringLiteral $data
     * @return OrganizerCreationPayload
     */
    public function deserialize(StringLiteral $data)
    {
        $data = parent::deserialize($data);
        $this->validator->validate($data);

        $url = Url::fromNative($data['website']);
        $address = null;
        $contactPoint = null;

        if (isset($data['address'])) {
            $address = $this->addressDeserializer->deserialize(
                new StringLiteral(
                    json_encode($data['address'])
                )
            );
        }

        if (isset($data['contact'])) {
            $contactPoint = $this->contactPointDeserializer->deserialize(
                new StringLiteral(
                    json_encode($data['contact'])
                )
            );
        }

        return new OrganizerCreationPayload(
            new Language($data['mainLanguage']),
            $url,
            new Title($data['name']),
            $address,
            $contactPoint
        );
    }
}
