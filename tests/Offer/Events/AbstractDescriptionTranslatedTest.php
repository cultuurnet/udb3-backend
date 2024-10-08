<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Item\Events\DescriptionTranslated;
use PHPUnit\Framework\TestCase;

class AbstractDescriptionTranslatedTest extends TestCase
{
    protected AbstractDescriptionTranslated $descriptionTranslatedEvent;

    protected string $itemId;

    protected Language $language;

    protected Description $description;

    public function setUp(): void
    {
        $this->itemId = 'Foo';
        $this->language = new Language('en');
        $this->description = new Description('Description');
        $this->descriptionTranslatedEvent = new DescriptionTranslated(
            $this->itemId,
            $this->language,
            $this->description
        );
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_With_properties(): void
    {
        $expectedItemId = 'Foo';
        $expectedLanguage = new Language('en');
        $expectedDescription = new Description('Description');
        $expectedDescriptionTranslated = new DescriptionTranslated(
            $expectedItemId,
            $expectedLanguage,
            $expectedDescription
        );

        $this->assertEquals($expectedDescriptionTranslated, $this->descriptionTranslatedEvent);
    }

    /**
     * @test
     */
    public function it_can_return_its_properties(): void
    {
        $expectedItemId = 'Foo';
        $expectedLanguage = new Language('en');
        $expectedDescription = new Description('Description');

        $itemId = $this->descriptionTranslatedEvent->getItemId();
        $language = $this->descriptionTranslatedEvent->getLanguage();
        $description = $this->descriptionTranslatedEvent->getDescription();

        $this->assertEquals($expectedItemId, $itemId);
        $this->assertEquals($expectedLanguage, $language);
        $this->assertEquals($expectedDescription, $description);
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_to_an_array(
        array $expectedSerializedValue,
        DescriptionTranslated $descriptionTranslated
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $descriptionTranslated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_deserialize_an_array(
        array $serializedValue,
        DescriptionTranslated $expectedDescriptionTranslated
    ): void {
        $this->assertEquals(
            $expectedDescriptionTranslated,
            DescriptionTranslated::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
    {
        return [
            'abstractDescriptionTranslated' => [
                [
                    'item_id' => 'madId',
                    'language' => 'en',
                    'description' => 'Description',
                ],
                new DescriptionTranslated(
                    'madId',
                    new Language('en'),
                    new Description('Description')
                ),
            ],
        ];
    }
}
