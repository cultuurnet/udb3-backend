<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use PHPUnit\Framework\TestCase;

class TitleTranslatedTest extends TestCase
{
    private TitleTranslated $titleTranslated;

    private array $titleTranslatedAsArray;

    protected function setUp(): void
    {
        $organizerId = '3ad6c135-9b2d-4360-8886-3a58aaf66039';

        $title = 'Het Depot';

        $language = 'nl';

        $this->titleTranslated = new TitleTranslated(
            $organizerId,
            $title,
            $language
        );

        $this->titleTranslatedAsArray = [
            'organizer_id' => $organizerId,
            'title' => $title,
            'language' => $language,
        ];
    }

    /**
     * @test
     */
    public function it_can_serialize_to_an_array(): void
    {
        $this->assertEquals(
            $this->titleTranslatedAsArray,
            $this->titleTranslated->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize_from_an_array(): void
    {
        $this->assertEquals(
            TitleTranslated::deserialize($this->titleTranslatedAsArray),
            $this->titleTranslated
        );
    }
}
