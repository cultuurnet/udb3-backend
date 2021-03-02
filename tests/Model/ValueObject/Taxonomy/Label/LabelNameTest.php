<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label;

class LabelNameTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_stores_a_label_value()
    {
        $labelName = new LabelName('foo');
        $this->assertEquals('foo', $labelName->toString());
    }

    /**
     * @test
     */
    public function it_trims()
    {
        $labelName = new LabelName(' foo ');
        $this->assertEquals('foo', $labelName->toString());
    }

    /**
     * @test
     */
    public function it_does_not_support_semi_colons()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Label 'foo;bar' should not contain semicolons.");
        new LabelName('foo;bar');
    }

    /**
     * @test
     */
    public function it_requires_labels_of_at_least_2_characters()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Label 'f' should not be shorter than 2 chars.");
        new LabelName('f');
    }

    /**
     * @test
     */
    public function it_requires_labels_of_at_most_255_characters()
    {
        $longLabel = 'abcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyz
            abcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyz
            abcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyz
            abcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyz
            abcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyz
            abcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyz
            abcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyz
            abcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyz
            abcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyzabcdefghijklmnopqrtsuvwxyz';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Label '$longLabel' should not be longer than 255 chars.");
        new LabelName($longLabel);
    }
}
