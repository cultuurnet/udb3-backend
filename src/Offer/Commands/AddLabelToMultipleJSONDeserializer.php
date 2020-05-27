<?php

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\Deserializer\DeserializerInterface;
use CultuurNet\Deserializer\JSONDeserializer;
use CultuurNet\Deserializer\MissingValueException;
use CultuurNet\Deserializer\NotWellFormedException;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Offer\OfferIdentifierCollection;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * @todo Move to udb3-symfony-php.
 * @see https://jira.uitdatabank.be/browse/III-1436
 */
class AddLabelToMultipleJSONDeserializer extends JSONDeserializer
{
    /**
     * @var DeserializerInterface
     */
    private $offerIdentifierDeserializer;

    /**
     * @param DeserializerInterface $offerIdentifierDeserializer
     */
    public function __construct(DeserializerInterface $offerIdentifierDeserializer)
    {
        $this->offerIdentifierDeserializer = $offerIdentifierDeserializer;
    }

    /**
     * @param StringLiteral $data
     *
     * @return AddLabelToMultiple
     *
     * @throws NotWellFormedException
     */
    public function deserialize(StringLiteral $data)
    {
        $data = parent::deserialize($data);

        if (empty($data->label)) {
            throw new MissingValueException('Missing value "label".');
        }
        if (empty($data->offers)) {
            throw new MissingValueException('Missing value "offers".');
        }

        $label = new Label($data->label);
        $offers = new OfferIdentifierCollection();

        foreach ($data->offers as $offer) {
            $offers = $offers->with(
                $this->offerIdentifierDeserializer->deserialize(
                    new StringLiteral(
                        json_encode($offer)
                    )
                )
            );
        }

        return new AddLabelToMultiple($offers, $label);
    }
}
