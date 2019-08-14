<?php

namespace CultuurNet\UDB3\Curators;

use CultuurNet\UDB3\Label;
use PHPUnit\Framework\TestCase;

class LabelFactoryTest extends TestCase
{
    /**
     * @var LabelFactory
     */
    private $labelFactory;

    protected function setUp()
    {
        $this->labelFactory = new LabelFactory(
            [
                'bruzz' => 'BRUZZ-redactioneel',
            ]
        );
    }

    /**
     * @test
     */
    public function it_will_create_label_for_known_publishers()
    {
        $expected = new Label('BRUZZ-redactioneel', false);
        $label = $this->labelFactory->forPublisher(Publisher::bruzz());

        $this->assertEquals($expected, $label);
    }
}
