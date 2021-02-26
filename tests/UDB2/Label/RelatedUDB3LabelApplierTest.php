<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\Label;

use Broadway\Domain\AggregateRoot;
use CultuurNet\UDB3\Event\Event;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface as LabelsRepositoryInterface;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\LabelRelation;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface as LabelsRelationsRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Organizer\Organizer;
use CultuurNet\UDB3\Place\Place;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class RelatedUDB3LabelApplierTest extends TestCase
{
    /**
     * @var LabelsRelationsRepositoryInterface|MockObject
     */
    private $labelsRelationsRepository;

    /**
     * @var LabelsRepositoryInterface|MockObject
     */
    private $labelsRepository;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var RelatedUDB3LabelApplier
     */
    private $nativeLabelApplier;

    protected function setUp()
    {
        $this->labelsRelationsRepository = $this->createMock(
            LabelsRelationsRepositoryInterface::class
        );

        $this->labelsRepository = $this->createMock(
            LabelsRepositoryInterface::class
        );

        $this->logger = $this->createMock(
            LoggerInterface::class
        );

        $this->nativeLabelApplier = new RelatedUDB3LabelApplier(
            $this->labelsRelationsRepository,
            $this->labelsRepository,
            $this->logger
        );
    }

    /**
     * @test aggregateDataProvider
     * @dataProvider aggregateDataProvider
     * @param AggregateRoot|Event|Place|Organizer $aggregateRoot
     */
    public function it_can_apply_labels(
        AggregateRoot $aggregateRoot,
        RelationType $relationType
    ) {
        $relationId = new StringLiteral($aggregateRoot->getAggregateRootId());

        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                [
                    'Found udb3 label 2dotstwice for aggregate 4968976e-1b0f-4400-849f-54db45731c43',
                ],
                [
                    'Added udb3 label 2dotstwice for aggregate 4968976e-1b0f-4400-849f-54db45731c43',
                ]
            );

        $this->labelsRelationsRepository->expects($this->once())
            ->method('getLabelRelationsForItem')
            ->with(
                $relationId
            )
            ->willReturn(
                [
                    new LabelRelation(
                        new LabelName('2dotstwice'),
                        $relationType,
                        $relationId,
                        false
                    ),
                    new LabelRelation(
                        new LabelName('Cultuurnet'),
                        $relationType,
                        $relationId,
                        true
                    ),
                ]
            );

        $this->labelsRepository->expects($this->once())
            ->method('getByName')
            ->with(
                new LabelName('2dotstwice')
            )
            ->willReturn(
                new Entity(
                    new UUID(),
                    new StringLiteral('2dotstwice'),
                    Visibility::INVISIBLE(),
                    Privacy::PRIVACY_PUBLIC()
                )
            );

        $this->nativeLabelApplier->apply($aggregateRoot);
    }

    /**
     * @return array
     */
    public function aggregateDataProvider()
    {
        return [
            'Apply label on event' => [
                $this->createAggregate(Event::class),
                RelationType::EVENT(),
            ],
            'Apply label on place' => [
                $this->createAggregate(Place::class),
                RelationType::PLACE(),
            ],
            'Apply label on organizer' => [
                $this->createAggregate(Organizer::class),
                RelationType::ORGANIZER(),
            ],
        ];
    }

    /**
     * @param string $aggregateType
     * @return MockObject
     */
    private function createAggregate($aggregateType)
    {
        $aggregate = $this->createMock($aggregateType);

        $aggregate->method('getAggregateRootId')
            ->willReturn('4968976e-1b0f-4400-849f-54db45731c43');

        $aggregate->expects($this->once())
            ->method('addLabel')
            ->with($this->callback(
                function (Label $label) {
                    return $label->equals(new Label('2dotstwice', false));
                }
            ));

        return $aggregate;
    }
}
