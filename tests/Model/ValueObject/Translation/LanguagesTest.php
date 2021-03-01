<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Text;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Translation\Languages;
use PHPUnit\Framework\TestCase;

class LanguagesTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_filter_out_duplicates()
    {
        $given = [
            new Language('nl'),
            new Language('fr'),
            new Language('en'),
            new Language('fr'),
            new Language('de'),
        ];

        $expected = [
            new Language('nl'),
            new Language('fr'),
            new Language('en'),
            new Language('de'),
        ];

        $collection = new Languages(...$given);
        $actual = $collection->toArray();

        $this->assertEquals($expected, $actual);
    }
}
