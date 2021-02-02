<?php

namespace CultuurNet\UDB3\Label\ValueObjects;

use PHPUnit\Framework\TestCase;

class RelationTypeTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_an_event_option()
    {
        $relationsType = RelationType::EVENT();

        $this->assertEquals($relationsType, RelationType::EVENT);
    }

    /**
     * @test
     */
    public function it_has_a_place_option()
    {
        $relationsType = RelationType::PLACE();

        $this->assertEquals($relationsType, RelationType::PLACE);
    }

    /**
     * @test
     */
    public function it_has_an_organizer_option()
    {
        $relationsType = RelationType::ORGANIZER();

        $this->assertEquals($relationsType, RelationType::ORGANIZER);
    }

    /**
     * @test
     */
    public function it_has_only_an_event_and_place_and_organizer_option()
    {
        $options = RelationType::getConstants();

        $this->assertEquals(
            [
                RelationType::EVENT()->getName() => RelationType::EVENT,
                RelationType::PLACE()->getName() => RelationType::PLACE,
                RelationType::ORGANIZER()->getName() => RelationType::ORGANIZER,
            ],
            $options
        );
    }
}
