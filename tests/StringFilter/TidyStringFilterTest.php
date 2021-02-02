<?php

namespace CultuurNet\UDB3\StringFilter;

class TidyStringFilterTest extends StringFilterTest
{
    /**
     * {@inheritdoc}
     */
    protected $filterClass = TidyStringFilter::class;

    /**
     * @test
     * Original event with this kind of broken tag
     * ID: 0c8ce12f-a9e7-4d9f-9e53-7a3a21510a4a
     * broken event XML included in even_with_broken_xml_tag.xml
     */
    public function it_escapes_broken_html_end_tags()
    {
        $element_with_valid_tag = "<p>Valid Element</p>";
        $broken_html_end_tag = "</...";

        $original = $element_with_valid_tag .
            $broken_html_end_tag .
            $element_with_valid_tag;

        $expected = $element_with_valid_tag . PHP_EOL .
            "&lt;/..." . PHP_EOL .
            $element_with_valid_tag . PHP_EOL;

        $this->assertFilterValue($expected, $original);
    }
}
