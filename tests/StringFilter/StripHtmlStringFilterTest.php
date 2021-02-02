<?php

namespace CultuurNet\UDB3\StringFilter;

class StripHtmlStringFilterTest extends StringFilterTest
{
    /**
     * {@inheritdoc}
     */
    protected $filterClass = StripHtmlStringFilter::class;

    /**
     * @test
     *
     * @dataProvider htmlStringDataProvider
     */
    public function it_converts_html_strings_to_plain_text($original, $expected)
    {
        $this->assertFilterValue($expected, $original);
    }

    /**
     * Provides test data for converting paragraphs and breaks into newlines.
     *
     * @return array
     *   Array of arrays, each individual array contains all arguments for the test method.
     */
    public function htmlStringDataProvider()
    {
        $single_newline = 'Line 1.' . PHP_EOL . 'Line 2.';
        $double_newline = 'Line 1.' . PHP_EOL . PHP_EOL . 'Line 2.';
        return array(
            array('<span>Lorem ipsum</span> <strong>dolor</strong>.', 'Lorem ipsum dolor.'),
            array('Lorem &amp; ipsum.', 'Lorem & ipsum.'),
            array('Lorem & ipsum.', 'Lorem & ipsum.'),
            array('Line 1.<br />Line 2.<br />', $single_newline),
            array('Line 1.<br />' . PHP_EOL . 'Line 2.', $single_newline),
            array('<br />Line 1.<br /><br />Line 2.<br />', $double_newline),
            array('<br />Line 1.<br /><br /><br />Line 2.<br />', $double_newline),
            array('<p>Line 1.</p><p>Line 2.</p>', $double_newline),
            array('<p>Line 1.</p>' . PHP_EOL . '<p>Line 2.</p>', $double_newline),
            array('<p>Line 1.</p><br /><p>Line 2.</p><br /><br />', $double_newline),
            array('<p>Line 1.</p><br /><br /><p>Line 2.</p><br /><br />', $double_newline),
        );
    }
}
