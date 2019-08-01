<?php

namespace CultuurNet\UDB3\Model\Import\PreProcessing;

use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface as LabelRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\LabelRelation;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface as LabelRelationsRepository;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\Import\DecodedDocument;
use CultuurNet\UDB3\Model\Import\DocumentImporterInterface;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class LabelPreProcessingDocumentImporterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DocumentImporterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $importer;

    /**
     * @var LabelRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $labelsRepository;

    /**
     * @var LabelRelationsRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $labelRelationsRepository;

    /**
     * @var LabelServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $labelService;

    /**
     * @var LabelPreProcessingDocumentImporter
     */
    private $labelPreProcessingDocumentImporter;

    protected function setUp()
    {
        $this->importer = $this->createMock(DocumentImporterInterface::class);
        $this->labelsRepository = $this->createMock(LabelRepository::class);
        $this->labelRelationsRepository = $this->createMock(LabelRelationsRepository::class);
        $this->labelService = $this->createMock(LabelServiceInterface::class);

        $this->labelPreProcessingDocumentImporter = new LabelPreProcessingDocumentImporter(
            $this->importer,
            $this->labelsRepository,
            $this->labelRelationsRepository,
            $this->labelService
        );
    }

    /**
     * @test
     */
    public function it_can_supplement_udb3_labels()
    {
        $decodedDocument = new DecodedDocument(
            '9f34efc7-a528-4ea8-a53e-a183f21abbab',
            [
                '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
                'labels' => [
                    'imported_new_visible_label',
                    'udb3_visible_label',
                    'udb3_must_be_hidden',
                ],
                'hiddenLabels' => [
                    'imported_new_hidden_label',
                    'udb3_hidden_label',
                    'udb3_must_be_visible',
                ],
            ]
        );

        $this->labelRelationsRepository->expects($this->once())
            ->method('getLabelRelationsForItem')
            ->with(new StringLiteral('9f34efc7-a528-4ea8-a53e-a183f21abbab'))
            ->willReturn(
                [
                    new LabelRelation(
                        new LabelName('udb3_visible_label'),
                        RelationType::EVENT(),
                        new StringLiteral('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
                        false
                    ),
                    new LabelRelation(
                        new LabelName('udb3_must_be_hidden'),
                        RelationType::EVENT(),
                        new StringLiteral('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
                        false
                    ),
                    new LabelRelation(
                        new LabelName('udb3_hidden_label'),
                        RelationType::EVENT(),
                        new StringLiteral('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
                        false
                    ),
                    new LabelRelation(
                        new LabelName('udb3_must_be_visible'),
                        RelationType::EVENT(),
                        new StringLiteral('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
                        false
                    ),
                    new LabelRelation(
                        new LabelName('udb3_missing_visible_label'),
                        RelationType::EVENT(),
                        new StringLiteral('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
                        false
                    ),
                    new LabelRelation(
                        new LabelName('udb3_missing_hidden_label'),
                        RelationType::EVENT(),
                        new StringLiteral('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
                        false
                    ),
                    new LabelRelation(
                        new LabelName('imported_old_hidden_label'),
                        RelationType::EVENT(),
                        new StringLiteral('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
                        true
                    ),
                ]
            );

        $this->labelsRepository->expects($this->exactly(6))
            ->method('getByName')
            ->willReturnCallback(function (StringLiteral $labelName) {
                if ($labelName->sameValueAs(new LabelName('udb3_visible_label'))) {
                    return new Entity(
                        new UUID('94b863a8-715c-418b-a422-34ad941c6a48'),
                        new StringLiteral('udb3_visible_label'),
                        Visibility::VISIBLE(),
                        Privacy::PRIVACY_PUBLIC()
                    );
                }

                if ($labelName->sameValueAs(new LabelName('udb3_must_be_hidden'))) {
                    return new Entity(
                        new UUID('94b863a8-715c-418b-a422-34ad941c6a48'),
                        new StringLiteral('udb3_must_be_hidden'),
                        Visibility::INVISIBLE(),
                        Privacy::PRIVACY_PUBLIC()
                    );
                }

                if ($labelName->sameValueAs(new LabelName('udb3_hidden_label'))) {
                    return new Entity(
                        new UUID('94b863a8-715c-418b-a422-34ad941c6a48'),
                        new StringLiteral('udb3_hidden_label'),
                        Visibility::INVISIBLE(),
                        Privacy::PRIVACY_PUBLIC()
                    );
                }

                if ($labelName->sameValueAs(new LabelName('udb3_must_be_visible'))) {
                    return new Entity(
                        new UUID('94b863a8-715c-418b-a422-34ad941c6a48'),
                        new StringLiteral('udb3_must_be_visible'),
                        Visibility::VISIBLE(),
                        Privacy::PRIVACY_PUBLIC()
                    );
                }

                if ($labelName->sameValueAs(new LabelName('udb3_missing_visible_label'))) {
                    return new Entity(
                        new UUID('94b863a8-715c-418b-a422-34ad941c6a48'),
                        new StringLiteral('udb3_missing_visible_label'),
                        Visibility::VISIBLE(),
                        Privacy::PRIVACY_PUBLIC()
                    );
                }

                if ($labelName->sameValueAs(new LabelName('udb3_missing_hidden_label'))) {
                    return new Entity(
                        new UUID('94b863a8-715c-418b-a422-34ad941c6a48'),
                        new StringLiteral('udb3_missing_hidden_label'),
                        Visibility::INVISIBLE(),
                        Privacy::PRIVACY_PUBLIC()
                    );
                }

                return null;
            });

        $this->labelService->expects($this->exactly(2))
            ->method('createLabelAggregateIfNew')
            ->withConsecutive(
                [
                    new LabelName('imported_new_visible_label'),
                    true,
                ],
                [
                    new LabelName('imported_new_hidden_label'),
                    false,
                ]
            );

        $expectedDecodedDocument = new DecodedDocument(
            '9f34efc7-a528-4ea8-a53e-a183f21abbab',
            [
                '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
                'labels' => [
                    'imported_new_visible_label',
                    'udb3_visible_label',
                    'udb3_must_be_visible',
                    'udb3_missing_visible_label',
                ],
                'hiddenLabels' => [
                    'imported_new_hidden_label',
                    'udb3_must_be_hidden',
                    'udb3_hidden_label',
                    'udb3_missing_hidden_label',
                ],
            ]
        );

        $this->importer->expects($this->once())
            ->method('import')
            ->with($expectedDecodedDocument);

        $this->labelPreProcessingDocumentImporter->import($decodedDocument);
    }

    /**
     * @test
     */
    public function it_does_not_add_empty_label_arrays()
    {
        $decodedDocument = new DecodedDocument(
            '9f34efc7-a528-4ea8-a53e-a183f21abbab',
            [
                '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            ]
        );

        $expectedDecodedDocument = new DecodedDocument(
            '9f34efc7-a528-4ea8-a53e-a183f21abbab',
            [
                '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
            ]
        );

        $this->labelRelationsRepository->expects($this->once())
            ->method('getLabelRelationsForItem')
            ->with(new StringLiteral('9f34efc7-a528-4ea8-a53e-a183f21abbab'))
            ->willReturn([]);

        $this->importer->expects($this->once())
            ->method('import')
            ->with($expectedDecodedDocument);

        $this->labelPreProcessingDocumentImporter->import($decodedDocument);
    }
}
