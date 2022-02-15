<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Deserializer\DeserializerInterface;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Deserializer\NotWellFormedException;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class IriOfferIdentifierJSONDeserializer implements DeserializerInterface
{
    /**
     * @var IriOfferIdentifierFactoryInterface
     */
    private $iriOfferIdentifierFactory;

    /**
     * IriOfferIdentifierJSONDeserializer constructor.
     */
    public function __construct(IriOfferIdentifierFactoryInterface $iriOfferIdentifierFactory)
    {
        $this->iriOfferIdentifierFactory = $iriOfferIdentifierFactory;
    }

    /**
     * @return IriOfferIdentifier
     */
    public function deserialize(StringLiteral $data)
    {
        $data = json_decode($data->toNative(), true);

        if (null === $data) {
            throw new NotWellFormedException('Invalid JSON');
        }

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
