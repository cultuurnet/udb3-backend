<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use RuntimeException;

class IriOfferIdentifierFactory implements IriOfferIdentifierFactoryInterface
{
    private string $regex;

    public function __construct(string $regex)
    {
        $this->regex = $regex;

        $match = @preg_match(
            '@^' . $regex . '$@',
            '',
            $matches
        );

        if (false === $match) {
            throw new \InvalidArgumentException(
                'Problem evaluating regular expression pattern ' . $regex
            );
        }
    }

    /**
     * @return IriOfferIdentifier
     */
    public function fromIri(Url $iri)
    {
        $match = @preg_match(
            '@^' . $this->regex . '$@',
            $iri->toString(),
            $matches
        );

        if (0 === $match) {
            throw new RuntimeException(
                'The given URL can not be used. It might not be a cultural event, or no integration is provided with the system the cultural event is located at.'
            );
        }

        if (!array_key_exists('offertype', $matches)) {
            throw new \InvalidArgumentException(
                'Regular expression pattern should capture group named "offertype"'
            );
        }

        if (!array_key_exists('offerid', $matches)) {
            throw new \InvalidArgumentException(
                'Regular expression pattern should capture group named "offerid"'
            );
        }

        $offerType = OfferType::fromCaseInsensitiveValue($matches['offertype']);
        $offerId = $matches['offerid'];

        return new IriOfferIdentifier($iri, $offerId, $offerType);
    }
}
