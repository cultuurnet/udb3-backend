<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\StringFilter;

class BreakTagToNewlineStringFilterTest extends StringFilterTest
{
    protected function getFilter(): StringFilterInterface
    {
        return new BreakTagToNewlineStringFilter();
    }

    /**
     * @test
     */
    public function it_converts_break_tags_to_newlines(): void
    {
        $original = 'Hello<br>world!<br/>Goodbye!<br />Nice to have known you!';
        $expected = "Hello\nworld!\nGoodbye!\nNice to have known you!";
        $this->assertFilterValue($expected, $original);
    }

    /**
     * @test
     */
    public function it_converts_consecutive_break_tags_to_consecutive_newlines(): void
    {
        $original = 'Hello<br /><br />world!';
        $expected = "Hello\n\nworld!";
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
