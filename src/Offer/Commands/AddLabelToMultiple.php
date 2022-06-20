<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Offer\OfferIdentifierCollection;

class AddLabelToMultiple
{
    /**
     * @var OfferIdentifierCollection
     */
    protected $offerIdentifiers;

    protected Label $label;


    public function __construct(OfferIdentifierCollection $offerIdentifiers, Label $label)
    {
        $this->offerIdentifiers = $offerIdentifiers;
        $this->label = $label;
    }

    /**
     * @return OfferIdentifierCollection
     */
    public function getOfferIdentifiers()
    {
        return $this->offerIdentifiers;
    }

    public function getLabel(): Label
    {
        return $this->label;
    }
}
