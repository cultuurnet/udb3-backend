<?php

namespace CultuurNet\UDB3\Curators;

use CultuurNet\UDB3\Label;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LabelFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_will_create_label_for_known_publishers()
    {
        $labelFactory = new LabelFactory(
            [
                'bruzz' => 'BRUZZ-redactioneel',
            ]
        );
        $expected = new Label('BRUZZ-redactioneel', false);
        $label = $labelFactory->forPublisher(Publisher::fromName('bruzz'));

        $this->assertEquals($expected, $label);
    }

    /**
     * @test
     */
    public function it_will_throw_an_exception_for_unknown_publishers()
    {
        $labelFactory = new LabelFactory(
            [
                'SOME_PUBLISHER' => 'SOME_LABEL',
            ]
        );

        $this->expectException(InvalidArgumentException::class);
        $labelFactory->forPublisher(Publisher::fromName('bruzz'));
    }
}
