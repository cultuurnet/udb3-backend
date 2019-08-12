<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Taxonomy\Label;

use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\LabelRelation;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName as Udb3LabelName;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

final class RelationshipModelLockedLabelRepositoryTest extends TestCase
{
    /**
     * @var ReadRepositoryInterface|MockObject
     */
    private $relationshipsRepository;

    /**
     * @var RelationshipModelLockedLabelRepository
     */
    private $lockedLabelRepository;

    protected function setUp()
    {
        $this->relationshipsRepository = $this->createMock(ReadRepositoryInterface::class);
        $this->lockedLabelRepository = new RelationshipModelLockedLabelRepository($this->relationshipsRepository);
    }

    /**
     * @test
     */
    public function it_returns_non_imported_labels_related_to_the_given_item()
    {
        $itemId = 'A09D5CAA-DFD7-43D1-BEE1-1307ECB3A1C1';

        $relations = [
            new LabelRelation(
                new Udb3LabelName('applied_via_ui_1'),
                RelationType::EVENT(),
                new StringLiteral($itemId),
                false
            ),
            new LabelRelation(
                new Udb3LabelName('imported_1'),
                RelationType::EVENT(),
                new StringLiteral($itemId),
                true
            ),
            new LabelRelation(
                new Udb3LabelName('applied_via_ui_2'),
                RelationType::EVENT(),
                new StringLiteral($itemId),
                false
            ),
            new LabelRelation(
                new Udb3LabelName('imported_2'),
                RelationType::EVENT(),
                new StringLiteral($itemId),
                true
            ),
        ];

        $this->relationshipsRepository->expects($this->once())
            ->method('getLabelRelationsForItem')
            ->with($itemId)
            ->willReturn($relations);

        $expectedLabels = new Labels(
            new Label(new LabelName('applied_via_ui_1')),
            new Label(new LabelName('applied_via_ui_2'))
        );

        $actualLabels = $this->lockedLabelRepository->getLockedLabelsForItem($itemId);

        $this->assertEquals($expectedLabels, $actualLabels);
    }
}
