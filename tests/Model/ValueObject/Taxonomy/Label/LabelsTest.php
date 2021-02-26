<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label;

use PHPUnit\Framework\TestCase;

class LabelsTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_throw_an_exception_if_duplicate_labels_with_the_same_visibility_are_given()
    {
        $name = new LabelName('foo');
        $label = new Label($name, true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Found 1 duplicates in the given array.');

        new Labels($label, $label);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_duplicate_labels_with_different_visibility_are_given()
    {
        $name = new LabelName('foo');
        $visibleLabel = new Label($name, true);
        $invisibleLabel = new Label($name, false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Found 1 duplicates in the given array.');

        new Labels($visibleLabel, $invisibleLabel);
    }
}
