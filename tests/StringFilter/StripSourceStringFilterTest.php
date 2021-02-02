<?php

namespace CultuurNet\UDB3\StringFilter;

class StripSourceStringFilterTest extends StringFilterTest
{
    /**
     * {@inheritdoc}
     */
    protected $filterClass = StripSourceStringFilter::class;

    /**
     * @test
     */
    public function it_strips_the_source_element_from_between_other_html_tags()
    {
        // @codingStandardsIgnoreStart
        $source_element = '<p class="uiv-source">Bron: <a href="http://www.uitinvlaanderen.be/agenda/e/fleuramour-passion-for-flowers/c2950cd0-d9e6-49f8-99fc-cffa4f004a20">UiTinVlaanderen.be</a></p>';
        // @codingStandardsIgnoreEnd
        $some_element = '<p>Some Element</p>';
        $another_element = '<p>Another Element</p>';

        $original = $some_element .
            $source_element .
            $another_element;

        $expected = $some_element .
            $another_element;

        $this->assertFilterValue($expected, $original);
    }

    /**
     * @test
     */
    public function it_strips_the_source_element_from_between_text_and_plain_text()
    {
        // @codingStandardsIgnoreStart
        $source_element = '<p class="uiv-source">Bron: <a href="http://www.uitinvlaanderen.be/agenda/e/fleuramour-passion-for-flowers/c2950cd0-d9e6-49f8-99fc-cffa4f004a20">UiTinVlaanderen.be</a></p>';
        // @codingStandardsIgnoreEnd
        $some_element = '<p>Some Element</p>';
        $without_element = "I'm some text without an element";

        $original = $without_element .
            $source_element .
            $some_element;

        $expected = $without_element .
            $some_element;

        $this->assertFilterValue($expected, $original);
    }

    /**
     * @test
     */
    public function it_strips_the_source_element_from_between_a_tag_and_plain_text()
    {
        // @codingStandardsIgnoreStart
        $source_element = '<p class="uiv-source">Bron: <a href="http://www.uitinvlaanderen.be/agenda/e/fleuramour-passion-for-flowers/c2950cd0-d9e6-49f8-99fc-cffa4f004a20">UiTinVlaanderen.be</a></p>';
        // @codingStandardsIgnoreEnd
        $some_element = '<p>Some Element</p>';
        $without_element = "I'm some text without an element";

        $original = $some_element .
            $source_element .
            $without_element;

        $expected = $some_element .
            $without_element;

        $this->assertFilterValue($expected, $original);
    }

    /**
     * @test
     */
    public function it_strips_the_source_element_from_between_a_plain_text()
    {
        // @codingStandardsIgnoreStart
        $source_element = '<p class="uiv-source">Bron: <a href="http://www.uitinvlaanderen.be/agenda/e/fleuramour-passion-for-flowers/c2950cd0-d9e6-49f8-99fc-cffa4f004a20">UiTinVlaanderen.be</a></p>';
        // @codingStandardsIgnoreEnd
        $without_element = "I'm some text without an element";

        $original = $without_element .
            $source_element .
            $without_element;

        $expected = $without_element .
            $without_element;

        $this->assertFilterValue($expected, $original);
    }

    /**
     * @test
     */
    public function it_ignores_empty_strings()
    {
        // In the past passing an empty string to StripSourceStringFilter
        // would cause a notice. If the test doesn't fail on this notice,
        // it does not occur anymore.
        $this->filter('');
        $this->expectNotToPerformAssertions();
    }

    /**
     * @test
     */
    public function it_only_filters_strings()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->filter->filter(12345);
    }
}
