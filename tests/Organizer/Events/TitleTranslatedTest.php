<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\TestCase;

class TitleTranslatedTest extends TestCase
{
    /**
     * @var string
     */
    private $organizerId;

    private string $title;

    private string $language;

    /**
     * @var TitleTranslated
     */
    private $titleTranslated;

    /**
     * @var array
     */
    private $titleTranslatedAsArray;

    protected function setUp()
    {
        $this->organizerId = '3ad6c135-9b2d-4360-8886-3a58aaf66039';

        $this->title = 'Het Depot';

        $this->language = 'nl';

        $this->titleTranslated = new TitleTranslated(
            $this->organizerId,
            $this->title,
            $this->language
        );

        $this->titleTranslatedAsArray = [
            'organizer_id' =>  $this->organizerId,
            'title' => $this->title,
            'language' => $this->language,
        ];
    }

    /**
     * @test
     */
    public function it_can_serialize_to_an_array()
    {
        $this->assertEquals(
            $this->titleTranslatedAsArray,
            $this->titleTranslated->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize_from_an_array()
    {
        $this->assertEquals(
            TitleTranslated::deserialize($this->titleTranslatedAsArray),
            $this->titleTranslated
        );
    }
}
