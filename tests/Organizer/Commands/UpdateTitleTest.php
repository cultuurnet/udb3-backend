<?php

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\TestCase;

class UpdateTitleTest extends TestCase
{
    /**
     * @var string
     */
    private $organizerId;

    /**
     * @var Title
     */
    private $title;

    /**
     * @var Language
     */
    private $language;

    /**
     * @var UpdateTitle
     */
    private $updateTitle;

    protected function setUp()
    {
        $this->organizerId = '3c16f422-33ea-4a5b-b70c-dd22b9fddcba';

        $this->title = new Title('Het Depot');

        $this->language = new Language('nl');

        $this->updateTitle = new UpdateTitle(
            $this->organizerId,
            $this->title,
            $this->language
        );
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id()
    {
        $this->assertEquals(
            $this->organizerId,
            $this->updateTitle->getOrganizerId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_title()
    {
        $this->assertEquals(
            $this->title,
            $this->updateTitle->getTitle()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_language()
    {
        $this->assertEquals(
            $this->language,
            $this->updateTitle->getLanguage()
        );
    }
}
