<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

class UpdateTitleTest extends TestCase
{
    private string $organizerId;

    private Title $title;

    private Language $language;

    private UpdateTitle $updateTitle;

    protected function setUp(): void
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
    public function it_stores_an_organizer_id(): void
    {
        $this->assertEquals(
            $this->organizerId,
            $this->updateTitle->getOrganizerId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_title(): void
    {
        $this->assertEquals(
            $this->title,
            $this->updateTitle->getTitle()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_language(): void
    {
        $this->assertEquals(
            $this->language,
            $this->updateTitle->getLanguage()
        );
    }
}
