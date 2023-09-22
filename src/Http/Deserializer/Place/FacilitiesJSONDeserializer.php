<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Place;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Facility;
use CultuurNet\UDB3\Offer\OfferFacilityResolverInterface;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\RequiredPropertiesDataValidator;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class FacilitiesJSONDeserializer extends JSONDeserializer
{
    private OfferFacilityResolverInterface $facilityResolver;

    private DataValidatorInterface $validator;

    public function __construct(OfferFacilityResolverInterface $facilityResolver)
    {
        parent::__construct(true);

        $this->validator = new RequiredPropertiesDataValidator(['facilities']);
        $this->facilityResolver = $facilityResolver;
    }

    /**
     * @throws DataValidationException
     * @return Facility[]
     */
    public function deserialize(string $data): array
    {
        $data = parent::deserialize($data);
        $this->validator->validate($data);


        if (!is_array($data['facilities'])) {
            throw new DataValidationException('The facilities property should contain a list of ids');
        }

        return array_map(
            function ($facilityId) {
                return $this->facilityResolver->byId($facilityId);
            },
            array_unique($data['facilities'])
        );
    }
}
