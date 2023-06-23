<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LabelFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_will_create_label_for_known_publishers(): void
    {
        $labelFactory = new LabelFactory(
            [
                'bruzz' => 'BRUZZ-redactioneel',
            ]
        );
        $expected = new Label(new LabelName('BRUZZ-redactioneel'), false);
        $label = $labelFactory->forPublisher(new PublisherName('bruzz'));

        $this->assertEquals($expected, $label);
    }

    /**
     * @test
     */
    public function it_will_create_label_for_known_publishers_case_insensitively(): void
    {
        $labelFactory = new LabelFactory(
            [
                'bruzz' => 'BRUZZ-redactioneel',
            ]
        );
        $expected = new Label(new LabelName('BRUZZ-redactioneel'), false);
        $label = $labelFactory->forPublisher(new PublisherName('Bruzz'));

        $this->assertEquals($expected, $label);
    }

    /**
     * @test
     */
    public function it_will_throw_an_exception_for_unknown_publishers(): void
    {
        $labelFactory = new LabelFactory(
            [
                'SOME_PUBLISHER' => 'SOME_LABEL',
            ]
        );

        $this->expectException(InvalidArgumentException::class);
        $labelFactory->forPublisher(new PublisherName('bruzz'));
    }
}
