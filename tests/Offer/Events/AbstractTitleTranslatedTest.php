<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Item\Events\TitleTranslated;
use PHPUnit\Framework\TestCase;

class AbstractTitleTranslatedTest extends TestCase
{
    protected AbstractTitleTranslated $titleTranslatedEvent;

    protected string $itemId;

    protected Language $language;

    protected string $title;

    public function setUp(): void
    {
        $this->itemId = 'Foo';
        $this->language = new Language('en');
        $this->title = 'Title';
        $this->titleTranslatedEvent = new TitleTranslated($this->itemId, $this->language, $this->title);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_With_properties(): void
    {
        $expectedItemId = 'Foo';
        $expectedLanguage = new Language('en');
        $expectedTitleTranslated = new TitleTranslated(
            $expectedItemId,
            $expectedLanguage,
            'Title'
        );

        $this->assertEquals($expectedTitleTranslated, $this->titleTranslatedEvent);
    }

    /**
     * @test
     */
    public function it_can_return_its_properties(): void
    {
        $expectedItemId = 'Foo';
        $expectedLanguage = new Language('en');
        $expectedTitle = 'Title';

        $itemId = $this->titleTranslatedEvent->getItemId();
        $language = $this->titleTranslatedEvent->getLanguage();
        $title = $this->titleTranslatedEvent->getTitle();

        $this->assertEquals($expectedItemId, $itemId);
        $this->assertEquals($expectedLanguage, $language);
        $this->assertEquals($expectedTitle, $title);
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_to_an_array(
        array $expectedSerializedValue,
        TitleTranslated $titleTranslated
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $titleTranslated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_deserialize_an_array(
        array $serializedValue,
        TitleTranslated $expectedTitleTranslated
    ): void {
        $this->assertEquals(
            $expectedTitleTranslated,
            TitleTranslated::deserialize($serializedValue)
        );
    }

    /**
     * @return array
     */
    public function serializationDataProvider()
    {
        return [
            'abstractTitleTranslated' => [
                [
                    'item_id' => 'madId',
                    'language' => 'en',
                    'title' => 'Title',
                ],
                new TitleTranslated(
                    'madId',
                    new Language('en'),
                    'Title'
                ),
            ],
        ];
    }
}
