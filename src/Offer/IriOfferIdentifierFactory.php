<?php

namespace CultuurNet\UDB3\Offer;

use RuntimeException;
use ValueObjects\Web\Url;

class IriOfferIdentifierFactory implements IriOfferIdentifierFactoryInterface
{
    /**
     * @var string
     */
    private $regex;

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
     * @param Url $iri
     * @return IriOfferIdentifier
     */
    public function fromIri(Url $iri)
    {
        $match = @preg_match(
            '@^' . $this->regex . '$@',
            (string)$iri,
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
