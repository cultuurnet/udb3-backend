<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Address;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Geography\AddressDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;

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

        return (new AddressDenormalizer())->denormalize($data, Address::class);
    }
}
