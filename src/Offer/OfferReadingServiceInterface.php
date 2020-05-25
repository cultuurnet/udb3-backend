<?php

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\ReadModel\JsonDocument;

interface OfferReadingServiceInterface
{
    /**
     * Loads and returns a JsonDocument matching a given IRI.
     *
     * @param string $iri
     *  Can represent any type of offer.
     *
     * @return JsonDocument
     */
    public function load($iri);
}
