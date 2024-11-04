<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\IriOfferIdentifier;
use CultuurNet\UDB3\Offer\OfferIdentifierCollection;
use CultuurNet\UDB3\Offer\OfferType;
use PHPUnit\Framework\TestCase;

class AddLabelToMultipleTest extends TestCase
{
    protected AddLabelToMultiple $labelMultiple;

    protected OfferIdentifierCollection $offerIdentifiers;

    protected Label $label;

    public function setUp(): void
    {
        $this->offerIdentifiers = OfferIdentifierCollection::fromArray(
            [
                new IriOfferIdentifier(
                    new Url('http://du.de/event/1'),
                    '1',
                    OfferType::event()
                ),
                new IriOfferIdentifier(
                    new Url('http://du.de/event/2'),
                    '2',
                    OfferType::event()
                ),
                new IriOfferIdentifier(
                    new Url('http://du.de/event/3'),
                    '3',
                    OfferType::event()
                ),
            ]
        );

        $this->label = new Label(new LabelName('testlabel'));

        $this->labelMultiple = new AddLabelToMultiple(
            $this->offerIdentifiers,
            $this->label
        );
    }

    /**
     * @test
     */
    public function it_returns_the_correct_property_values(): void
    {
        $this->assertEquals($this->offerIdentifiers, $this->labelMultiple->getOfferIdentifiers());
        $this->assertEquals($this->label, $this->labelMultiple->getLabel());
    }
}
