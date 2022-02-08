<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\IriOfferIdentifier;
use CultuurNet\UDB3\Offer\OfferIdentifierCollection;
use CultuurNet\UDB3\Offer\OfferType;
use PHPUnit\Framework\TestCase;
use ValueObjects\Web\Url as LegacyUrl;

class AddLabelToMultipleTest extends TestCase
{
    /**
     * @var AddLabelToMultiple
     */
    protected $labelMultiple;

    /**
     * @var OfferIdentifierCollection
     */
    protected $offerIdentifiers;

    /**
     * @var Label
     */
    protected $label;

    public function setUp()
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

        $this->label = new Label('testlabel');

        $this->labelMultiple = new AddLabelToMultiple(
            $this->offerIdentifiers,
            $this->label
        );
    }

    /**
     * @test
     */
    public function it_returns_the_correct_property_values()
    {
        $this->assertEquals($this->offerIdentifiers, $this->labelMultiple->getOfferIdentifiers());
        $this->assertEquals($this->label, $this->labelMultiple->getLabel());
    }
}
