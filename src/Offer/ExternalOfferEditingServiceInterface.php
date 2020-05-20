<?php

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Label;

interface ExternalOfferEditingServiceInterface
{
    /**
     * @param IriOfferIdentifier $identifier
     * @param Label $label
     */
    public function addLabel(IriOfferIdentifier $identifier, Label $label);
}
