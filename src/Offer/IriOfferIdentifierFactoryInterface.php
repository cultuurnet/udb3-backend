<?php

namespace CultuurNet\UDB3\Offer;

use ValueObjects\Web\Url;

interface IriOfferIdentifierFactoryInterface
{
    /**
     * @param Url $iri
     * @return IriOfferIdentifier
     */
    public function fromIri(Url $iri);
}
