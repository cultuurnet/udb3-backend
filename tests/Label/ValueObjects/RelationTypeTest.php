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

        $this->assertEquals($relationsType, RelationType::event());
    }

    /**
     * @test
     */
    public function it_has_a_place_option(): void
    {
        $relationsType = new RelationType('Place');

        $this->assertEquals($relationsType, RelationType::place());
    }

    /**
     * @test
     */
    public function it_has_an_organizer_option(): void
    {
        $relationsType = new RelationType('Organizer');

        $this->assertEquals($relationsType, RelationType::organizer());
    }

    /**
     * @test
     */
    public function it_has_only_an_event_and_place_and_organizer_option(): void
    {
        $options = RelationType::getAllowedValues();

        $this->assertEquals(
            [
                RelationType::event()->toString(),
                RelationType::place()->toString(),
                RelationType::organizer()->toString(),
            ],
            $options
        );
    }
}
