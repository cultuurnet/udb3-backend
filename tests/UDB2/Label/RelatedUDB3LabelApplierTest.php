<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\Label;

use Broadway\Domain\AggregateRoot;
use CultuurNet\UDB3\Event\Event;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface as LabelsRepositoryInterface;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\LabelRelation;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface as LabelsRelationsRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName as LegacyLabelName;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Organizer\Organizer;
use CultuurNet\UDB3\Place\Place;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use CultuurNet\UDB3\StringLiteral;

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

    private RelatedUDB3LabelApplier $nativeLabelApplier;

    protected function setUp(): void
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
    ): void {
        $relationId = $aggregateRoot->getAggregateRootId();

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
                        '2dotstwice',
                        $relationType,
                        $relationId,
                        false
                    ),
                    new LabelRelation(
                        'Cultuurnet',
                        $relationType,
                        $relationId,
                        true
                    ),
                ]
            );

        $this->labelsRepository->expects($this->once())
            ->method('getByName')
            ->with(
                new LegacyLabelName('2dotstwice')
            )
            ->willReturn(
                new Entity(
                    new UUID('ecedf33c-d4ea-4f62-a6e8-a7bcdf839bbe'),
                    new LabelName('2dotstwice'),
                    Visibility::INVISIBLE(),
                    Privacy::PRIVACY_PUBLIC()
                )
            );

        $this->nativeLabelApplier->apply($aggregateRoot);
    }

    public function aggregateDataProvider(): array
    {
        return [
            'Apply label on event' => [
                $this->createAggregate(Event::class),
                RelationType::event(),
            ],
            'Apply label on place' => [
                $this->createAggregate(Place::class),
                RelationType::place(),
            ],
            'Apply label on organizer' => [
                $this->createAggregate(Organizer::class),
                RelationType::organizer(),
            ],
        ];
    }

    private function createAggregate(string $aggregateType): MockObject
    {
        $aggregate = $this->createMock($aggregateType);

        $aggregate->method('getAggregateRootId')
            ->willReturn('4968976e-1b0f-4400-849f-54db45731c43');

        $aggregate->expects($this->once())
            ->method('addLabel')
            ->with($this->callback(
                function (Label $label) {
                    return $label->getName()->sameAs(
                        (new Label(new LabelName('2dotstwice'), false))->getName()
                    );
                }
            ));

        return $aggregate;
    }
}
