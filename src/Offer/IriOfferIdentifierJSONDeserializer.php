<?php

namespace CultuurNet\UDB3\Offer;

use CultuurNet\Deserializer\DeserializerInterface;
use CultuurNet\Deserializer\MissingValueException;
use CultuurNet\Deserializer\NotWellFormedException;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

/**
 * @todo Move to udb3-symfony-php.
 * @see https://jira.uitdatabank.be/browse/III-1436
 */
class IriOfferIdentifierJSONDeserializer implements DeserializerInterface
{
    /**
     * @var IriOfferIdentifierFactoryInterface
     */
    private $iriOfferIdentifierFactory;

    /**
     * IriOfferIdentifierJSONDeserializer constructor.
     * @param IriOfferIdentifierFactoryInterface $iriOfferIdentifierFactory
     */
    public function __construct(IriOfferIdentifierFactoryInterface $iriOfferIdentifierFactory)
    {
        $this->iriOfferIdentifierFactory = $iriOfferIdentifierFactory;
    }

    /**
     * @param StringLiteral $data
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
            Url::fromNative($data['@id'])
        );
    }
}
