<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;

interface IriOfferIdentifierFactoryInterface
{
    public function fromIri(Url $iri): IriOfferIdentifier;
}
