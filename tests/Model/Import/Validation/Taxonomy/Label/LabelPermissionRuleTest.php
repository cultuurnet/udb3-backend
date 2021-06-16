<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Label;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface as LabelsRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\LabelRelation;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface as LabelRelationsRepository;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Security\UserIdentificationInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Exceptions\ValidationException;
use ValueObjects\StringLiteral\StringLiteral;

class LabelPermissionRuleTest extends TestCase
{
    /**
     * @var UUID
     */
    private $documentId;

    /**
     * @var string
     */
    private $userId;

    /**
     * @var LabelsRepository|MockObject
     */
    private $labelsRepository;

    /**
     * @var LabelRelationsRepository|MockObject
     */
    private $labelRelationsRepository;

    /**
     * @var LabelPermissionRule
     */
    private $labelPermissionRule;

    protected function setUp()
    {
        $this->documentId = new UUID('f32227be-a621-4cbd-8803-19762d7f9a23');

        $this->userId = 'user_id';

        $this->labelsRepository = $this->createMock(LabelsRepository::class);

        $this->labelRelationsRepository = $this->createMock(LabelRelationsRepository::class);

        $this->labelPermissionRule = new LabelPermissionRule(
            $this->documentId,
            $this->userId,
            $this->labelsRepository,
            $this->labelRelationsRepository
        );
    }

    /**
     * @test
     */
    public function it_does_not_delegates_validation_to_label_repository_for_existing_labels_and_non_god_user()
    {
        $this->labelRelationsRepository->expects($this->once())
            ->method('getLabelRelationsForItem')
            ->with(new StringLiteral($this->documentId->toString()))
            ->willReturn([
                new LabelRelation(
                    new \CultuurNet\UDB3\Label\ValueObjects\LabelName('foo'),
                    RelationType::EVENT(),
                    new StringLiteral($this->documentId->toString()),
                    false
                ),
                new LabelRelation(
                    new \CultuurNet\UDB3\Label\ValueObjects\LabelName('bar'),
                    RelationType::EVENT(),
                    new StringLiteral($this->documentId->toString()),
                    false
                ),
            ]);

        $this->labelsRepository->expects($this->never())
            ->method('canUseLabel');

        $this->assertTrue(
            $this->labelPermissionRule->validate('foo')
        );
    }

    /**
     * @test
     */
    public function it_delegates_validation_to_label_repository_for_new_labels_and_non_god_user()
    {
        $this->labelRelationsRepository->expects($this->once())
            ->method('getLabelRelationsForItem')
            ->with(new StringLiteral($this->documentId->toString()))
            ->willReturn([
                new LabelRelation(
                    new \CultuurNet\UDB3\Label\ValueObjects\LabelName('bar'),
                    RelationType::EVENT(),
                    new StringLiteral($this->documentId->toString()),
                    false
                ),
            ]);

        $this->labelsRepository->expects($this->once())
            ->method('canUseLabel')
            ->with($this->userId, new StringLiteral('foo'))
            ->willReturn(true);

        $this->assertTrue(
            $this->labelPermissionRule->validate('foo')
        );
    }

    /**
     * @test
     */
    public function it_creates_label_permission_rule_exception_on_assert()
    {
        $this->labelRelationsRepository->expects($this->once())
            ->method('getLabelRelationsForItem')
            ->with(new StringLiteral($this->documentId->toString()))
            ->willReturn([
                new LabelRelation(
                    new \CultuurNet\UDB3\Label\ValueObjects\LabelName('bar'),
                    RelationType::EVENT(),
                    new StringLiteral($this->documentId->toString()),
                    false
                ),
            ]);

        $this->labelsRepository->expects($this->once())
            ->method('canUseLabel')
            ->with($this->userId, new StringLiteral('foo'))
            ->willReturn(false);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('no permission to use label foo');

        $this->labelPermissionRule->assert('foo');
    }
}
