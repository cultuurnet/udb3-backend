<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Label;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface as LabelsRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface as LabelRelationsRepository;
use CultuurNet\UDB3\Model\Event\EventIDParser;
use CultuurNet\UDB3\Security\UserIdentificationInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Exceptions\ValidationException;
use ValueObjects\StringLiteral\StringLiteral;

class DocumentLabelPermissionRuleTest extends TestCase
{
    /**
     * @var EventIDParser
     */
    private $uuidParser;

    /**
     * @var UserIdentificationInterface|MockObject
     */
    private $userIdentification;

    /**
     * @var LabelsRepository|MockObject
     */
    private $labelsRepository;

    /**
     * @var LabelRelationsRepository|MockObject
     */
    private $labelRelationsRepository;

    /**
     * @var DocumentLabelPermissionRule
     */
    private $documentLabelPermissionRule;

    protected function setUp()
    {
        $this->uuidParser = new EventIDParser();

        $this->userIdentification = $this->createMock(UserIdentificationInterface::class);

        $this->labelsRepository = $this->createMock(LabelsRepository::class);

        $this->labelRelationsRepository = $this->createMock(LabelRelationsRepository::class);

        $this->documentLabelPermissionRule = new DocumentLabelPermissionRule(
            $this->uuidParser,
            $this->userIdentification,
            $this->labelsRepository,
            $this->labelRelationsRepository
        );
    }

    /**
     * @test
     */
    public function it_validates_to_true_when_id_is_missing()
    {
        $document = [
            'labels' => [
                'foo',
                'bar',
            ],
            'hiddenLabels' => [
                'lorem',
                'ipsum',
            ],
        ];

        $this->assertTrue(
            $this->documentLabelPermissionRule->validate($document)
        );
    }

    /**
     * @test
     */
    public function it_validates_to_true_when_id_is_not_a_valid_url()
    {
        $document = [
            '@id' => 'https:io.uitdatabank.be/events/c33b4498-0932-4fbe-816f-c6641f30ba3b',
            'labels' => [
                'foo',
                'bar',
            ],
            'hiddenLabels' => [
                'lorem',
                'ipsum',
            ],
        ];

        $this->assertTrue(
            $this->documentLabelPermissionRule->validate($document)
        );
    }

    /**
     * @test
     */
    public function it_validates_to_true_when_id_is_malformed()
    {
        $document = [
            '@id' => 'https://io.uitdatabank.be/events/i_am_not_a_uuid',
            'labels' => [
                'foo',
                'bar',
            ],
            'hiddenLabels' => [
                'lorem',
                'ipsum',
            ],
        ];

        $this->assertTrue(
            $this->documentLabelPermissionRule->validate($document)
        );
    }

    /**
     * @test
     */
    public function it_validates_all_labels_on_a_document()
    {
        $document = [
            '@id' => 'https://io.uitdatabank.be/events/c33b4498-0932-4fbe-816f-c6641f30ba3b',
            'labels' => [
                'foo',
                'bar',
            ],
            'hiddenLabels' => [
                'lorem',
                'ipsum',
            ],
        ];

        $userId = new StringLiteral('user_id');

        $this->labelRelationsRepository->expects($this->exactly(4))
            ->method('getLabelRelationsForItem')
            ->with(new StringLiteral('c33b4498-0932-4fbe-816f-c6641f30ba3b'))
            ->willReturn([]);

        $this->labelsRepository->expects($this->exactly(4))
            ->method('canUseLabel')
            ->willReturnCallback(function (StringLiteral $userId, StringLiteral $name) {
                if ($name->toNative() === 'foo' || $name->toNative() === 'lorem') {
                    return true;
                } else {
                    return false;
                }
            });

        $this->userIdentification->expects($this->exactly(4))
            ->method('getId')
            ->willReturn($userId);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('no permission to use labels bar, ipsum');

        $this->documentLabelPermissionRule->assert($document);
    }
}
