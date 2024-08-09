<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Deserializer\DeserializerInterface;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class IriOfferIdentifierJSONDeserializer implements DeserializerInterface
{
    private IriOfferIdentifierFactoryInterface $iriOfferIdentifierFactory;

    /**
     * IriOfferIdentifierJSONDeserializer constructor.
     */
    public function __construct(IriOfferIdentifierFactoryInterface $iriOfferIdentifierFactory)
    {
        $this->iriOfferIdentifierFactory = $iriOfferIdentifierFactory;
    }

    public function deserialize(string $data): IriOfferIdentifier
    {
        $data = Json::decodeAssociatively($data);

        if (!isset($data['@id'])) {
            throw new MissingValueException('Missing property "@id".');
        }
        //@TODO III-826 Remove type property.
        if (!isset($data['@type'])) {
            throw new MissingValueException('Missing property "@type".');
        }

        return $this->iriOfferIdentifierFactory->fromIri(
            new Url($data['@id'])
        );
    }
}
