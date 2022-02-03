<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Import;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface as LabelRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\LabelRelation;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface as LabelRelationsRepository;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class ImportLabelVisibilityRequestBodyParserTest extends TestCase
{
    private MockObject $labelsRepository;
    private MockObject $labelRelationsRepository;
    private ImportLabelVisibilityRequestBodyParser $importLabelVisibilityRequestBodyParser;

    protected function setUp(): void
    {
        $this->labelsRepository = $this->createMock(LabelRepository::class);
        $this->labelRelationsRepository = $this->createMock(LabelRelationsRepository::class);

        $this->importLabelVisibilityRequestBodyParser = new ImportLabelVisibilityRequestBodyParser(
            $this->labelsRepository,
            $this->labelRelationsRepository
        );
    }

    /**
     * @test
     */
    public function it_can_add_missing_labels_that_have_been_added_via_the_udb3_ui_before_and_need_to_be_kept(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withParsedBody(
                (object) [
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
            )
            ->build('POST');

        $this->labelRelationsRepository->expects($this->once())
            ->method('getLabelRelationsForItem')
            ->with(new StringLiteral('9f34efc7-a528-4ea8-a53e-a183f21abbab'))
            ->willReturn(
                [
                    new LabelRelation(
                        new LabelName('udb3_visible_label'),
                        RelationType::event(),
                        new StringLiteral('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
                        false
                    ),
                    new LabelRelation(
                        new LabelName('udb3_must_be_hidden'),
                        RelationType::event(),
                        new StringLiteral('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
                        false
                    ),
                    new LabelRelation(
                        new LabelName('udb3_hidden_label'),
                        RelationType::event(),
                        new StringLiteral('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
                        false
                    ),
                    new LabelRelation(
                        new LabelName('udb3_must_be_visible'),
                        RelationType::event(),
                        new StringLiteral('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
                        false
                    ),
                    new LabelRelation(
                        new LabelName('udb3_missing_visible_label'),
                        RelationType::event(),
                        new StringLiteral('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
                        false
                    ),
                    new LabelRelation(
                        new LabelName('udb3_missing_hidden_label'),
                        RelationType::event(),
                        new StringLiteral('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
                        false
                    ),
                    new LabelRelation(
                        new LabelName('imported_old_hidden_label'),
                        RelationType::event(),
                        new StringLiteral('9f34efc7-a528-4ea8-a53e-a183f21abbab'),
                        true
                    ),
                ]
            );

        $this->labelsRepository->expects($this->exactly(8))
            ->method('getByName')
            ->willReturnCallback(function (StringLiteral $labelName) {
                if ($labelName->sameValueAs(new StringLiteral('udb3_visible_label'))) {
                    return new Entity(
                        new UUID('94b863a8-715c-418b-a422-34ad941c6a48'),
                        new StringLiteral('udb3_visible_label'),
                        Visibility::VISIBLE(),
                        Privacy::PRIVACY_PUBLIC()
                    );
                }

                if ($labelName->sameValueAs(new StringLiteral('udb3_must_be_hidden'))) {
                    return new Entity(
                        new UUID('94b863a8-715c-418b-a422-34ad941c6a48'),
                        new StringLiteral('udb3_must_be_hidden'),
                        Visibility::INVISIBLE(),
                        Privacy::PRIVACY_PUBLIC()
                    );
                }

                if ($labelName->sameValueAs(new StringLiteral('udb3_hidden_label'))) {
                    return new Entity(
                        new UUID('94b863a8-715c-418b-a422-34ad941c6a48'),
                        new StringLiteral('udb3_hidden_label'),
                        Visibility::INVISIBLE(),
                        Privacy::PRIVACY_PUBLIC()
                    );
                }

                if ($labelName->sameValueAs(new StringLiteral('udb3_must_be_visible'))) {
                    return new Entity(
                        new UUID('94b863a8-715c-418b-a422-34ad941c6a48'),
                        new StringLiteral('udb3_must_be_visible'),
                        Visibility::VISIBLE(),
                        Privacy::PRIVACY_PUBLIC()
                    );
                }

                if ($labelName->sameValueAs(new StringLiteral('udb3_missing_visible_label'))) {
                    return new Entity(
                        new UUID('94b863a8-715c-418b-a422-34ad941c6a48'),
                        new StringLiteral('udb3_missing_visible_label'),
                        Visibility::VISIBLE(),
                        Privacy::PRIVACY_PUBLIC()
                    );
                }

                if ($labelName->sameValueAs(new StringLiteral('udb3_missing_hidden_label'))) {
                    return new Entity(
                        new UUID('94b863a8-715c-418b-a422-34ad941c6a48'),
                        new StringLiteral('udb3_missing_hidden_label'),
                        Visibility::INVISIBLE(),
                        Privacy::PRIVACY_PUBLIC()
                    );
                }

                return null;
            });

        // Note that imported_new_hidden_label should be visible because even though it was in hiddenLabels and it had
        // no explicit config, new labels are always visible. Only admins can make a label hidden.
        $expectedRequest = $request->withParsedBody(
            (object) [
                '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
                'labels' => [
                    'imported_new_visible_label',
                    'udb3_visible_label',
                    'imported_new_hidden_label',
                    'udb3_must_be_visible',
                    'udb3_missing_visible_label',
                ],
                'hiddenLabels' => [
                    'udb3_must_be_hidden',
                    'udb3_hidden_label',
                    'udb3_missing_hidden_label',
                ],
            ]
        );

        $actualRequest = $this->importLabelVisibilityRequestBodyParser->parse($request);

        $this->assertEquals($expectedRequest, $actualRequest);
    }

    /**
     * @test
     */
    public function it_does_not_add_empty_labels_arrays(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withParsedBody(
                (object) [
                    '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
                    'hiddenLabels' => [
                        'foo',
                    ],
                ]
            )
            ->build('POST');

        $this->labelRelationsRepository->expects($this->once())
            ->method('getLabelRelationsForItem')
            ->with(new StringLiteral('9f34efc7-a528-4ea8-a53e-a183f21abbab'))
            ->willReturn([]);

        $this->labelsRepository->expects($this->any())
            ->method('getByName')
            ->willReturnCallback(function (StringLiteral $name) {
                if (!$name->sameValueAs(new StringLiteral('foo'))) {
                    return null;
                }
                return new Entity(
                    new UUID('f37caa6d-0b79-4638-a6fb-eefc9696df5b'),
                    $name,
                    Visibility::INVISIBLE(),
                    Privacy::PRIVACY_PUBLIC()
                );
            });

        $expectedRequest = $request->withParsedBody(
            (object) [
                '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
                'hiddenLabels' => [
                    'foo',
                ],
            ]
        );

        $actualRequest = $this->importLabelVisibilityRequestBodyParser->parse($request);

        $this->assertEquals($expectedRequest, $actualRequest);
    }

    /**
     * @test
     */
    public function it_does_not_add_empty_hiddenLabels_arrays(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withParsedBody(
                (object) [
                    '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
                    'labels' => [
                        'foo',
                    ],
                ]
            )
            ->build('POST');

        $this->labelRelationsRepository->expects($this->once())
            ->method('getLabelRelationsForItem')
            ->with(new StringLiteral('9f34efc7-a528-4ea8-a53e-a183f21abbab'))
            ->willReturn([]);

        $expectedRequest = $request->withParsedBody(
            (object) [
                '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
                'labels' => [
                    'foo',
                ],
            ]
        );

        $actualRequest = $this->importLabelVisibilityRequestBodyParser->parse($request);

        $this->assertEquals($expectedRequest, $actualRequest);
    }

    /**
     * @test
     */
    public function it_removes_duplicates_to_avoid_unnecessary_validation_errors(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withParsedBody(
                (object) [
                    '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
                    'labels' => [
                        'foo',
                        'foo',
                    ],
                    'hiddenLabels' => [
                        'foo',
                        'bar',
                    ],
                ]
            )
            ->build('POST');

        $this->labelRelationsRepository->expects($this->once())
            ->method('getLabelRelationsForItem')
            ->with(new StringLiteral('9f34efc7-a528-4ea8-a53e-a183f21abbab'))
            ->willReturn([]);

        $expectedRequest = $request->withParsedBody(
            (object) [
                '@id' => 'https://io.uitdatabank.be/event/9f34efc7-a528-4ea8-a53e-a183f21abbab',
                'labels' => [
                    'foo',
                    'bar',
                ],
            ]
        );

        $actualRequest = $this->importLabelVisibilityRequestBodyParser->parse($request);

        $this->assertEquals($expectedRequest, $actualRequest);
    }
}
