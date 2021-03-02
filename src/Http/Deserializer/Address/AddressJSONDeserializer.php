<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Address;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Address\Address;
use ValueObjects\StringLiteral\StringLiteral;

class AddressJSONDeserializer extends JSONDeserializer
{
    /**
     * @var AddressDataValidator
     */
    private $validator;

    public function __construct()
    {
        $assoc = true;
        parent::__construct($assoc);

        $this->validator = new AddressDataValidator();
    }

    /**
     * @return Address
     * @throws DataValidationException
     */
    public function deserialize(StringLiteral $data)
    {
        $data = parent::deserialize($data);
        $this->validator->validate($data);

        // @todo postalCode is documented as an integer in Swagger,
        // but should be a string. (Documented as Text on schema.org)
        $data['postalCode'] = (string) $data['postalCode'];

        return Address::deserialize($data);
    }
}
