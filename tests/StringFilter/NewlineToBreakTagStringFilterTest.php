<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\StringFilter;

class NewlineToBreakTagStringFilterTest extends StringFilterTest
{
    protected function getFilter(): StringFilterInterface
    {
        return new NewlineToBreakTagStringFilter();
    }

    /**
     * @test
     */
    public function it_converts_newlines_to_break_tags(): void
    {
        $original = "Hello\nworld!\nGoodbye!";
        $expected = 'Hello<br />world!<br />Goodbye!';
        $this->assertFilterValue($expected, $original);
    }

    /**
     * @test
     */
    public function it_converts_newlines_to_break_tags_without_closing_tag(): void
    {
        $original = "Hello\nworld!\nGoodbye!";
        $expected = 'Hello<br>world!<br>Goodbye!';

        $filter = new NewlineToBreakTagStringFilter();
        $filter->closeTag(false);

        $this->assertEquals($expected, $filter->filter($original));
    }

    /**
     * @test
     */
    public function it_converts_consecutive_newlines_to_consecutive_break_tags(): void
    {
        $original = "Hello\n\nworld!";
        $expected = 'Hello<br /><br />world!';
        $this->assertFilterValue($expected, $original);
    }

    /**
     * @test
     */
    public function it_only_filters_strings(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->filter->filter(12345);
    }
}
