<?php

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Language;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractUpdateDescriptionTest extends TestCase
{
    /**
     * @var AbstractUpdateDescription|MockObject
     */
    protected $updateDescriptionCommand;

    /**
     * @var string
     */
    protected $itemId;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var Language
     */
    protected $language;

    public function setUp()
    {
        $this->itemId = 'Foo';
        $this->description = new Description('This is the event description update.');
        $this->language = new Language('en');

        $this->updateDescriptionCommand = $this->getMockForAbstractClass(
            AbstractUpdateDescription::class,
            array($this->itemId, $this->language, $this->description)
        );
    }

    /**
     * @test
     */
    public function it_can_return_its_properties()
    {
        $description = $this->updateDescriptionCommand->getDescription();
        $expectedDescription = new Description('This is the event description update.');

        $this->assertEquals($expectedDescription, $description);

        $itemId = $this->updateDescriptionCommand->getItemId();
        $expectedItemId = 'Foo';

        $this->assertEquals($expectedItemId, $itemId);
    }

    /**
     * @test
     */
    public function it_should_keep_track_of_the_description_language()
    {
        $this->assertEquals(new Language('en'), $this->updateDescriptionCommand->getLanguage());
    }
}
