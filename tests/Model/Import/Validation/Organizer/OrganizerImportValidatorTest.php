<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Validation\Organizer;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface as LabelsRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface as LabelRelationsRepository;
use CultuurNet\UDB3\Model\Validation\Organizer\OrganizerValidator;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUIDParser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrganizerImportValidatorTest extends TestCase
{
    /**
     * @var UUIDParser|MockObject
     */
    private $uuidParser;

    /**
     * @var LabelsRepository|MockObject
     */
    private $labelsRepository;

    /**
     * @var LabelRelationsRepository|MockObject
     */
    private $labelRelationsRepository;

    protected function setUp()
    {
        $this->uuidParser = $this->createMock(UUIDParser::class);

        $this->labelsRepository = $this->createMock(LabelsRepository::class);

        $this->labelRelationsRepository = $this->createMock(LabelRelationsRepository::class);
    }

    /**
     * @test
     */
    public function it_creates_organizer_validator_for_document()
    {
        $organizerDocumentValidator = new OrganizerImportValidator(
            $this->uuidParser,
            'user_id',
            $this->labelsRepository,
            $this->labelRelationsRepository,
            true
        );

        $this->assertInstanceOf(OrganizerValidator::class, $organizerDocumentValidator);
    }
}
