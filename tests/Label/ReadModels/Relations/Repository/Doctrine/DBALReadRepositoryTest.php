<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine;

use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\LabelRelation;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;

class DBALReadRepositoryTest extends BaseDBALRepositoryTest
{
    private DBALReadRepository $readRepository;

    private LabelRelation $relation1;

    private LabelRelation $relation2;

    private LabelRelation $relation4;

    protected function setUp(): void
    {
        parent::setUp();

        $this->readRepository = new DBALReadRepository(
            $this->getConnection(),
            $this->getTableName()
        );

        $this->saveOfferLabelRelations();
    }

    /**
     * @test
     */
    public function it_should_return_relations_of_the_offers_that_are_tagged_with_a_specific_label(): void
    {
        $offerLabelRelations = [];
        foreach ($this->readRepository->getLabelRelations('2dotstwice') as $offerLabelRelation) {
            $offerLabelRelations[] = $offerLabelRelation;
        }

        $expectedRelations = [
            $this->relation1,
            $this->relation2,
        ];

        $this->assertEquals($expectedRelations, $offerLabelRelations);
    }

    /**
     * @test
     */
    public function it_returns_empty_array_when_no_relations_found_for_specific_label(): void
    {
        $offerLabelRelations = [];
        foreach ($this->readRepository->getLabelRelations('missing') as $offerLabelRelation) {
            $offerLabelRelations[] = $offerLabelRelation;
        }

        $this->assertEmpty($offerLabelRelations);
    }

    /**
     * @test
     */
    public function it_can_return_all_labels_for_a_relation_id(): void
    {
        $labelRelations = $this->readRepository->getLabelRelationsForItem('99A78F44-A45B-40E2-A1E3-7632D2F3B1C6');

        $this->assertEquals(
            [
                $this->relation1,
                $this->relation4,
            ],
            $labelRelations
        );
    }

    /**
     * @test
     */
    public function it_returns_an_empty_list_when_no_match_on_relation_id(): void
    {
        $labelRelations = $this->readRepository->getLabelRelationsForItem('89A78F44-A45B-40E2-A1E3-7632D2F3B1C5');

        $this->assertEmpty($labelRelations);
    }

    /**
     * @test
     */
    public function it_should_return_offers_by_label_for_type(): void
    {
        $labelRelationsForType = $this->readRepository->getLabelRelationsForType(
            'cultuurnet',
            RelationType::place()
        );

        $this->assertEquals(
            [
                '298A39A1-8D1E-4F5D-B05E-811B6459EA36',
                '99A78F44-A45B-40E2-A1E3-7632D2F3B1C6',
            ],
            $labelRelationsForType
        );
    }

    /**
     * @test
     */
    public function it_should_return_offers_by_multiple_labels_for_type(): void
    {
        $labelRelationsForType = $this->readRepository->getLabelsRelationsForType(
            ['cultuurnet', '2dotstwice'],
            RelationType::place()
        );

        $this->assertEquals(
            [
                '99A78F44-A45B-40E2-A1E3-7632D2F3B1C6',
                'A9B3FA7B-9AF5-49F4-8BB5-2B169CE83107',
                '298A39A1-8D1E-4F5D-B05E-811B6459EA36',
                '99A78F44-A45B-40E2-A1E3-7632D2F3B1C6',
            ],
            $labelRelationsForType
        );
    }

    private function saveOfferLabelRelations(): void
    {
        $labelName = '2dotstwice';

        $this->relation1 = new LabelRelation(
            $labelName,
            RelationType::place(),
            '99A78F44-A45B-40E2-A1E3-7632D2F3B1C6',
            false
        );

        $this->relation2 = new LabelRelation(
            $labelName,
            RelationType::place(),
            'A9B3FA7B-9AF5-49F4-8BB5-2B169CE83107',
            false
        );

        $relation3 = new LabelRelation(
            'cultuurnet',
            RelationType::place(),
            '298A39A1-8D1E-4F5D-B05E-811B6459EA36',
            false
        );

        $this->relation4 = new LabelRelation(
            'cultuurnet',
            RelationType::place(),
            '99A78F44-A45B-40E2-A1E3-7632D2F3B1C6',
            false
        );

        $relation5 = new LabelRelation(
            'cultuurnet',
            RelationType::event(),
            'e3d79147-7a2a-4c0c-ae34-2fcea72f8b5c',
            false
        );

        $this->saveLabelRelation($this->relation1);
        $this->saveLabelRelation($this->relation2);
        $this->saveLabelRelation($relation3);
        $this->saveLabelRelation($this->relation4);
        $this->saveLabelRelation($relation5);
    }
}
