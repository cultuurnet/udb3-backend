<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Deserializer\DeserializerInterface;
use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Offer\OfferIdentifierCollection;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class AddLabelToMultipleJSONDeserializer extends JSONDeserializer
{
    private DeserializerInterface $offerIdentifierDeserializer;

    public function __construct(DeserializerInterface $offerIdentifierDeserializer)
    {
        parent::__construct();
        $this->offerIdentifierDeserializer = $offerIdentifierDeserializer;
    }

    public function deserialize(string $data): AddLabelToMultiple
    {
        $data = parent::deserialize($data);

        if (empty($data->label)) {
            throw new MissingValueException('Missing value "label".');
        }
        if (empty($data->offers)) {
            throw new MissingValueException('Missing value "offers".');
        }

        $label = new Label(new LabelName($data->label));
        $offers = new OfferIdentifierCollection();

        foreach ($data->offers as $offer) {
            $offers = $offers->with(
                $this->offerIdentifierDeserializer->deserialize(Json::encode($offer))
            );
        }

        return new AddLabelToMultiple($offers, $label);
    }
}
