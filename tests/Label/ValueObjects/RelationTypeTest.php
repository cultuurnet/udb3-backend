<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ValueObjects;

use PHPUnit\Framework\TestCase;

class RelationTypeTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_an_event_option(): void
    {
        $relationsType = new RelationType('Event');

        $this->assertEquals($relationsType, RelationType::EVENT());
    }

    /**
     * @test
     */
    public function it_has_a_place_option(): void
    {
        $relationsType = new RelationType('Place');

        $this->assertEquals($relationsType, RelationType::PLACE());
    }

    /**
     * @test
     */
    public function it_has_an_organizer_option(): void
    {
        $relationsType = new RelationType('Organizer');

        $this->assertEquals($relationsType, RelationType::ORGANIZER());
    }

    /**
     * @test
     */
    public function it_has_only_an_event_and_place_and_organizer_option(): void
    {
        $options = RelationType::getAllowedValues();

        $this->assertEquals(
            [
                RelationType::EVENT()->toString(),
                RelationType::PLACE()->toString(),
                RelationType::ORGANIZER()->toString(),
            ],
            $options
        );
    }
}
