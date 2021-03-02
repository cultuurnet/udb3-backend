<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractUpdateTitleTest extends TestCase
{
    /**
     * @var AbstractUpdateTitle|MockObject
     */
    protected $updateTitleCommand;

    /**
     * @var string
     */
    protected $itemId;

    /**
     * @var Title
     */
    protected $title;

    /**
     * @var Language
     */
    protected $language;

    public function setUp()
    {
        $this->itemId = 'Foo';
        $this->title = new Title('This is the event title update.');
        $this->language = new Language('en');

        $this->updateTitleCommand = $this->getMockForAbstractClass(
            AbstractUpdateTitle::class,
            [$this->itemId, $this->language, $this->title]
        );
    }

    /**
     * @test
     */
    public function it_can_return_its_properties()
    {
        $title = $this->updateTitleCommand->getTitle();
        $expectedTitle = new Title('This is the event title update.');

        $this->assertEquals($expectedTitle, $title);

        $itemId = $this->updateTitleCommand->getItemId();
        $expectedItemId = 'Foo';

        $this->assertEquals($expectedItemId, $itemId);
    }

    /**
     * @test
     */
    public function it_should_keep_track_of_the_title_language()
    {
        $this->assertEquals(new Language('en'), $this->updateTitleCommand->getLanguage());
    }
}
