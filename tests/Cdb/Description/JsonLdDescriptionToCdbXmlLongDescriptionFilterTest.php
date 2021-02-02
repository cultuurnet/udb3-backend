<?php

namespace CultuurNet\UDB3\Cdb\Description;

use CultuurNet\UDB3\StringFilter\StringFilterTest;

class JsonLdDescriptionToCdbXmlLongDescriptionFilterTest extends StringFilterTest
{
    /**
     * @return JsonLdDescriptionToCdbXmlLongDescriptionFilter
     */
    protected function getFilter()
    {
        return new JsonLdDescriptionToCdbXmlLongDescriptionFilter();
    }

    /**
     * @test
     */
    public function it_should_convert_any_newlines_to_br_tags()
    {
        $description = "Beschrijving.\n\nRegel 2.\n\nRegel 3.\nRegel 4.";
        $expected = "Beschrijving.<br><br>Regel 2.<br><br>Regel 3.<br>Regel 4.";
        $actual = $this->filter($description);
        $this->assertEquals($expected, $actual);
    }
}
