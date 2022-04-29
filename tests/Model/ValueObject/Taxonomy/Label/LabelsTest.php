<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label;

use PHPUnit\Framework\TestCase;

class LabelsTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_filter_out_duplicates_and_always_use_visibility_of_the_last_duplicate(): void
    {
        $name = new LabelName('foo');
        $label = new Label($name, true);
        $labelHidden = new Label($name, false);

        $labels = new Labels($label, $labelHidden);

        $this->assertEquals([$labelHidden], $labels->toArray());
    }
}
