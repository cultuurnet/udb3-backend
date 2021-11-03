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
        $this->expectExceptionMessage("String 'foo;bar' does not match regex pattern /^[^;]{2,255}$/.");
        new LabelName('foo;bar');
    }

    /**
     * @test
     */
    public function it_requires_labels_of_at_least_2_characters()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("String 'f' does not match regex pattern /^[^;]{2,255}$/.");
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
        $this->expectExceptionMessage("String '$longLabel' does not match regex pattern /^[^;]{2,255}$/.");
        new LabelName($longLabel);
    }
}
