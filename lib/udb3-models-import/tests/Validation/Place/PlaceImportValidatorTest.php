<?php

namespace CultuurNet\UDB3\Model\Import\Validation\Place;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface as LabelsRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface as LabelRelationsRepository;
use CultuurNet\UDB3\Model\Validation\Place\PlaceValidator;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUIDParser;
use CultuurNet\UDB3\Security\UserIdentificationInterface;

class PlaceImportValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UUIDParser
     */
    private $uuidParser;

    /**
     * @var UserIdentificationInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $userIdentification;

    /**
     * @var LabelsRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $labelsRepository;

    /**
     * @var LabelRelationsRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $labelRelationsRepository;

    protected function setUp()
    {
        $this->uuidParser = $this->createMock(UUIDParser::class);

        $this->userIdentification = $this->createMock(UserIdentificationInterface::class);

        $this->labelsRepository = $this->createMock(LabelsRepository::class);

        $this->labelRelationsRepository = $this->createMock(LabelRelationsRepository::class);
    }

    /**
     * @test
     */
    public function it_creates_place_validator_for_document()
    {
        $placeDocumentValidator = new PlaceImportValidator(
            $this->uuidParser,
            $this->userIdentification,
            $this->labelsRepository,
            $this->labelRelationsRepository
        );

        $this->assertInstanceOf(PlaceValidator::class, $placeDocumentValidator);
    }
}
