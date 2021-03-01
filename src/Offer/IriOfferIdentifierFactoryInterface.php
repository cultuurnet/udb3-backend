<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use ValueObjects\Web\Url;

interface IriOfferIdentifierFactoryInterface
{
    /**
     * @return IriOfferIdentifier
     */
    public function fromIri(Url $iri);
}
