<?php

namespace CultuurNet\UDB3;

use CultureFeed_Cdb_Data_Keyword;
use PHPUnit\Framework\TestCase;

class LabelCollectionTest extends TestCase
{
    /**
     * @return array
     */
    public function notALabelProvider()
    {
        return [
            ['keyword 1'],
            [null],
            [1],
            [[]],
            [new \stdClass()],
        ];
    }

    /**
     * @test
     * @dataProvider notALabelProvider
     * @param mixed $notALabel
     */
    public function it_can_only_contain_labels($notALabel)
    {
        $this->expectException(\InvalidArgumentException::class);

        new LabelCollection(
            [
                $notALabel,
                new Label('foo'),
            ]
        );
    }

    /**
     * @test
     */
    public function it_can_intersect_a_label_collection()
    {
        $foodCollection = LabelCollection::fromStrings(['meat', 'pie', 'carrot', 'lettuce']);
        $plantCollection = LabelCollection::fromStrings(['carrot', 'lettuce', 'tree']);

        $ediblePlantCollection = $foodCollection->intersect($plantCollection);

        $expectedEdiblePlantCollection = LabelCollection::fromStrings(['carrot', 'lettuce']);

        $this->assertEquals($expectedEdiblePlantCollection, $ediblePlantCollection);
    }

    /**
     * @test
     */
    public function it_ignores_keywords_already_contained_in_the_current_collection()
    {
        $existingCollection = new LabelCollection(
            [
                new Label('keyword 1'),
            ]
        );

        $unchangedCollection = $existingCollection->with(new Label('keyword 1'));
        $this->assertEquals($existingCollection, $unchangedCollection);
    }

    /**
     * @test
     */
    public function it_ignores_invalid_labels_when_creating_from_string_array()
    {
        $labelsAsStrings = [
            'Correct label',
            'F',
            'This label is much too long and will also be ignored, just like the label F which is too short. But a few more extra characters are needed to make it fail! Like many aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaas',
            'Another correct label',
        ];

        $labelCollection = LabelCollection::fromStrings($labelsAsStrings);

        $expectedLabelCollection = new LabelCollection([
            new Label('Correct label'),
            new Label('Another correct label'),
        ]);

        $this->assertEquals($expectedLabelCollection, $labelCollection);
    }

    /**
     * @test
     */
    public function it_should_set_label_visibility_when_created_from_keywords()
    {
        $keywords = [
            new CultureFeed_Cdb_Data_Keyword('purple', true),
            new CultureFeed_Cdb_Data_Keyword('orange', false),
            new CultureFeed_Cdb_Data_Keyword('green', true),
        ];

        $labels = LabelCollection::fromKeywords($keywords);

        $expectedLabels = (new LabelCollection())
            ->with(new Label('purple', true))
            ->with(new Label('orange', false))
            ->with(new Label('green', true));

        $this->assertEquals($expectedLabels, $labels);
    }

    /**
     * @test
     */
    public function it_can_filter_a_label_collection()
    {
        $labelCollection = (new LabelCollection())
            ->with(new Label('purple', true))
            ->with(new Label('orange', false))
            ->with(new Label('green', true));

        $filteredLabelCollection = $labelCollection->filter(
            function (Label $label) {
                return $label->isVisible();
            }
        );

        $expectedLabelCollection = (new LabelCollection())
            ->with(new Label('purple', true))
            ->with(new Label('green', true));

        $this->assertEquals($expectedLabelCollection, $filteredLabelCollection);
    }
}
