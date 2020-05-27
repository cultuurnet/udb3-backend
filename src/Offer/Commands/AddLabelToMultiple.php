<?php


namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Offer\OfferIdentifierCollection;

class AddLabelToMultiple
{
    /**
     * @var OfferIdentifierCollection
     */
    protected $offerIdentifiers;

    /**
     * @var Label
     */
    protected $label;

    /**
     * @param OfferIdentifierCollection $offerIdentifiers
     * @param Label $label
     */
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

    /**
     * @return Label
     */
    public function getLabel()
    {
        return $this->label;
    }
}
