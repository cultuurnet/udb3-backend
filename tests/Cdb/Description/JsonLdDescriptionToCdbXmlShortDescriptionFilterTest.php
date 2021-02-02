<?php

namespace CultuurNet\UDB3\Cdb\Description;

use CultuurNet\UDB3\StringFilter\StringFilterTest;

class JsonLdDescriptionToCdbXmlShortDescriptionFilterTest extends StringFilterTest
{
    /**
     * @var string
     */
    protected $filterClass = JsonLdDescriptionToCdbXmlShortDescriptionFilter::class;

    /**
     * @test
     */
    public function it_should_strip_html_and_put_everything_on_a_single_line()
    {
        $long = ' <p>Lange <b>beschrijving</b>.</p>Regel 2.<br /><br />Regel 3. <br /> ';
        $expected = 'Lange beschrijving. Regel 2. Regel 3.';

        $actual = $this->filter($long);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_truncate_the_string_if_it_exceeds_400_characters()
    {
        // @codingStandardsIgnoreStart
        $long = '<p>Maecenas faucibus mollis interdum. Donec sed odio dui. Duis mollis, est non commodo luctus, nisi erat porttitor ligula, eget lacinia odio sem nec elit.</p><p>Donec id elit non mi porta gravida at eget metus. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor. Aenean lacinia bibendum nulla sed consectetur.</p><p>Maecenas faucibus mollis interdum. Donec sed odio dui. Duis mollis, est non commodo luctus, nisi erat porttitor ligula, eget lacinia odio sem nec elit.</p><p>Donec id elit non mi porta gravida at eget metus. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor. Aenean lacinia bibendum nulla sed consectetur.</p><p>Maecenas faucibus mollis interdum. Donec sed odio dui. Duis mollis, est non commodo luctus, nisi erat porttitor ligula, eget lacinia odio sem nec elit.</p><p>Donec id elit non mi porta gravida at eget metus. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor. Aenean lacinia bibendum nulla sed consectetur.</p>';
        $expected = 'Maecenas faucibus mollis interdum. Donec sed odio dui. Duis mollis, est non commodo luctus, nisi erat porttitor ligula, eget lacinia odio sem nec elit. Donec id elit non mi porta gravida at eget metus. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor. Aenean lacinia bibendum nulla sed consectetur. Maecenas faucibus mollis interdum. Donec sed odio dui. Duis mollis, est non ...';
        // @codingStandardsIgnoreEnd

        $actual = $this->filter($long);

        $this->assertEquals($expected, $actual);
    }
}
