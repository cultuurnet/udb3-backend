<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Address;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class AddressJSONDeserializer extends JSONDeserializer
{
    private AddressDataValidator $validator;

    public function __construct()
    {
        parent::__construct(true);

        $this->validator = new AddressDataValidator();
    }

    /**
     * @throws DataValidationException
     */
    public function deserialize(string $data): Address
    {
        $data = parent::deserialize($data);
        $this->validator->validate($data);

        // @todo postalCode is documented as an integer in Swagger,
        // but should be a string. (Documented as Text on schema.org)
        $data['postalCode'] = (string) $data['postalCode'];

        return new Address(
            new Street($data['streetAddress']),
            new PostalCode($data['postalCode']),
            new Locality($data['addressLocality']),
            new CountryCode($data['addressCountry'])
        );
    }
}
