<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Organizer;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Http\Deserializer\Address\AddressJSONDeserializer;
use CultuurNet\UDB3\Http\Deserializer\ContactPoint\ContactPointJSONDeserializer;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Title;
use CultuurNet\UDB3\StringLiteral;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class OrganizerCreationPayloadJSONDeserializer extends JSONDeserializer
{
    private OrganizerCreationPayloadDataValidator $validator;

    private AddressJSONDeserializer $addressDeserializer;

    private ContactPointJSONDeserializer $contactPointDeserializer;

    public function __construct()
    {
        parent::__construct(true);

        $this->validator = new OrganizerCreationPayloadDataValidator();

        $this->addressDeserializer = new AddressJSONDeserializer();
        $this->contactPointDeserializer = new ContactPointJSONDeserializer();
    }

    public function deserialize(StringLiteral $data): OrganizerCreationPayload
    {
        $data = parent::deserialize($data);
        $this->validator->validate($data);

        $url = new Url($data['website']);
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
