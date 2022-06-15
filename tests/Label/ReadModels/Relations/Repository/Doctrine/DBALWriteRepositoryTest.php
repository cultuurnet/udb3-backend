<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine;

use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\LabelRelation;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use CultuurNet\UDB3\StringLiteral;

class DBALWriteRepositoryTest extends BaseDBALRepositoryTest
{
    private DBALWriteRepository $dbalWriteRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbalWriteRepository = new DBALWriteRepository(
            $this->getConnection(),
            new StringLiteral($this->getTableName())
        );
    }

    /**
     * @test
     */
    public function it_can_save(): void
    {
        $expectedOfferLabelRelation = new LabelRelation(
            '2dotstwice',
            RelationType::place(),
            new StringLiteral('relationId'),
            true
        );

        $this->dbalWriteRepository->save(
            $expectedOfferLabelRelation->getLabelName(),
            $expectedOfferLabelRelation->getRelationType(),
            $expectedOfferLabelRelation->getRelationId()->toNative(),
            $expectedOfferLabelRelation->isImported()
        );

        $actualOfferLabelRelation = $this->getLabelRelations();

        $this->assertEquals([$expectedOfferLabelRelation], $actualOfferLabelRelation);
    }

    /**
     * @test
     */
    public function it_can_save_same_label_name_but_different_relation_type_and_relation_id(): void
    {
        $labelRelation1 = new LabelRelation(
            '2dotstwice',
            RelationType::place(),
            new StringLiteral('relationId'),
            false
        );

        $this->saveLabelRelation($labelRelation1);

        $labelRelation2 = new LabelRelation(
            $labelRelation1->getLabelName(),
            RelationType::event(),
            new StringLiteral('otherId'),
            true
        );

        $this->dbalWriteRepository->save(
            $labelRelation2->getLabelName(),
            $labelRelation2->getRelationType(),
            $labelRelation2->getRelationId()->toNative(),
            $labelRelation2->isImported()
        );

        $actualOfferLabelRelation = $this->getLabelRelations();

        $this->assertEquals(
            [
                $labelRelation1,
                $labelRelation2,
            ],
            $actualOfferLabelRelation
        );
    }

    /**
     * @test
     */
    public function it_can_save_same_label_name_and_relation_type_but_different_relation_id(): void
    {
        $labelRelation1 = new LabelRelation(
            '2dotstwice',
            RelationType::place(),
            new StringLiteral('relationId'),
            false
        );

        $this->saveLabelRelation($labelRelation1);

        $labelRelation2 = new LabelRelation(
            $labelRelation1->getLabelName(),
            $labelRelation1->getRelationType(),
            new StringLiteral('otherId'),
            true
        );

        $this->dbalWriteRepository->save(
            $labelRelation2->getLabelName(),
            $labelRelation2->getRelationType(),
            $labelRelation2->getRelationId()->toNative(),
            $labelRelation2->isImported()
        );

        $actualOfferLabelRelation = $this->getLabelRelations();

        $this->assertEquals(
            [
                $labelRelation1,
                $labelRelation2,
            ],
            $actualOfferLabelRelation
        );
    }

    /**
     * @test
     */
    public function it_can_not_save_same_offer_label_relation(): void
    {
        $offerLabelRelation = new LabelRelation(
            '2dotstwice',
            RelationType::place(),
            new StringLiteral('relationId'),
            true
        );

        $this->saveLabelRelation($offerLabelRelation);

        $sameOfferLabelRelation = new LabelRelation(
            $offerLabelRelation->getLabelName(),
            $offerLabelRelation->getRelationType(),
            $offerLabelRelation->getRelationId(),
            $offerLabelRelation->isImported()
        );

        $this->expectException(UniqueConstraintViolationException::class);

        $this->dbalWriteRepository->save(
            $sameOfferLabelRelation->getLabelName(),
            $sameOfferLabelRelation->getRelationType(),
            $sameOfferLabelRelation->getRelationId()->toNative(),
            $sameOfferLabelRelation->isImported()
        );
    }

    /**
     * @test
     */
    public function it_can_delete_based_on_label_name_and_relation_id(): void
    {
        $OfferLabelRelation1 = new LabelRelation(
            '2dotstwice',
            RelationType::place(),
            new StringLiteral('relationId'),
            false
        );

        $OfferLabelRelation2 = new LabelRelation(
            'cultuurnet',
            RelationType::place(),
            new StringLiteral('otherRelationId'),
            true
        );

        $this->saveLabelRelation($OfferLabelRelation1);
        $this->saveLabelRelation($OfferLabelRelation2);

        $this->dbalWriteRepository->deleteByLabelNameAndRelationId(
            $OfferLabelRelation1->getLabelName(),
            $OfferLabelRelation1->getRelationId()->toNative()
        );

        $labelRelations = $this->getLabelRelations();

        $this->assertCount(1, $labelRelations);

        $this->assertEquals(
            $OfferLabelRelation2->getLabelName(),
            $labelRelations[0]->getLabelName()
        );
    }

    /**
     * @test
     */
    public function it_can_delete_imported_labels_on_relation_id(): void
    {
        $labelRelations = $this->seedLabelRelations();

        $this->dbalWriteRepository->deleteImportedByRelationId(
            $labelRelations[0]->getRelationId()->toNative()
        );

        $foundLabelRelations = $this->getLabelRelations();

        $this->assertEquals(
            [
                $labelRelations[1],
                $labelRelations[2],
                $labelRelations[3],
            ],
            $foundLabelRelations
        );
    }

    /**
     * @return LabelRelation[]
     */
    private function seedLabelRelations(): array
    {
        $labelRelations = [
            new LabelRelation(
                '2dotstwice',
                RelationType::place(),
                new StringLiteral('relationId'),
                true
            ),
            new LabelRelation(
                'cultuurnet',
                RelationType::place(),
                new StringLiteral('otherRelationId'),
                false
            ),
            new LabelRelation(
                'cultuurnet',
                RelationType::place(),
                new StringLiteral('relationId'),
                false
            ),
            new LabelRelation(
                'foo',
                RelationType::place(),
                new StringLiteral('fooId'),
                false
            ),
        ];

        foreach ($labelRelations as $labelRelation) {
            $this->saveLabelRelation($labelRelation);
        }

        return $labelRelations;
    }
}
