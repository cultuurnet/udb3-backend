<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\ReadModel;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use PHPUnit\Framework\TestCase;

class SavedSearchTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_serialized_to_json(): void
    {
        $savedSearch = new SavedSearch(
            'In Leuven',
            new QueryString('city:"Leuven"'),
            '101'
        );

        $jsonEncoded = Json::encode($savedSearch);

        $this->assertEquals(
            '{"name":"In Leuven","query":"city:\"Leuven\"","id":"101"}',
            $jsonEncoded
        );
    }

    /**
     * @test
     */
    public function it_does_not_serialize_an_empty_id_property(): void
    {
        $savedSearch = new SavedSearch(
            'In Leuven',
            new QueryString('city:"Leuven"')
        );

        $jsonEncoded = Json::encode($savedSearch);

        $this->assertEquals(
            '{"name":"In Leuven","query":"city:\"Leuven\""}',
            $jsonEncoded
        );
    }
}
