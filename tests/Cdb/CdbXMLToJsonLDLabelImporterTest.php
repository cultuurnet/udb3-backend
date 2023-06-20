<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb;

use CultureFeed_Cdb_Data_Keyword;
use CultureFeed_Cdb_Item_Actor;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class CdbXMLToJsonLDLabelImporterTest extends TestCase
{
    private CdbXMLToJsonLDLabelImporter $cdbXMLToJsonLDLabelImporter;
    private MockObject $labelReadRepository;

    protected function setUp(): void
    {
        $this->labelReadRepository = $this->createMock(ReadRepositoryInterface::class);
        $this->cdbXMLToJsonLDLabelImporter = new CdbXMLToJsonLDLabelImporter($this->labelReadRepository);
    }

    /**
     * @test
     */
    public function it_fixes_label_visibility_if_different_on_imported_xml_than_in_udb3(): void
    {
        // Organizers used to be called "Actors" in UDB2, just like places.
        $organizer = new CultureFeed_Cdb_Item_Actor();
        $organizer->addKeyword(new CultureFeed_Cdb_Data_Keyword('visible_in_xml_and_visible_in_udb3', true));
        $organizer->addKeyword(new CultureFeed_Cdb_Data_Keyword('visible_in_xml_and_invisible_in_udb3', true));
        $organizer->addKeyword(new CultureFeed_Cdb_Data_Keyword('visible_in_xml_and_unknown_in_udb3', true));
        $organizer->addKeyword(new CultureFeed_Cdb_Data_Keyword('invisible_in_xml_and_invisible_in_udb3', false));
        $organizer->addKeyword(new CultureFeed_Cdb_Data_Keyword('invisible_in_xml_and_visible_in_udb3', false));
        $organizer->addKeyword(new CultureFeed_Cdb_Data_Keyword('invisible_in_xml_and_unknown_in_udb3', false));

        $this->labelReadRepository->expects($this->any())
            ->method('getByName')
            ->willReturnCallback(
                static function (string $labelName): ?Entity {
                    $uuid = new UUID('3b069d8a-2394-45f4-80ce-50469c643386');
                    $privacy = Privacy::PRIVACY_PUBLIC();

                    switch ($labelName) {
                        case 'visible_in_xml_and_visible_in_udb3':
                        case 'invisible_in_xml_and_visible_in_udb3':
                            return new Entity($uuid, $labelName, Visibility::VISIBLE(), $privacy);

                        case 'visible_in_xml_and_invisible_in_udb3':
                        case 'invisible_in_xml_and_invisible_in_udb3':
                            return new Entity($uuid, $labelName, Visibility::INVISIBLE(), $privacy);

                        default:
                            return null;
                    }
                }
            );

        $jsonLd = new stdClass();

        $this->cdbXMLToJsonLDLabelImporter->importLabels($organizer, $jsonLd);

        $expectedLabels = [
            'visible_in_xml_and_visible_in_udb3',
            'visible_in_xml_and_unknown_in_udb3',
            'invisible_in_xml_and_visible_in_udb3',
        ];

        $expectedHiddenLabels = [
            'visible_in_xml_and_invisible_in_udb3',
            'invisible_in_xml_and_invisible_in_udb3',
            'invisible_in_xml_and_unknown_in_udb3',
        ];

        $this->assertTrue(isset($jsonLd->labels));
        $this->assertTrue(isset($jsonLd->hiddenLabels));
        $this->assertEquals($expectedLabels, $jsonLd->labels);
        $this->assertEquals($expectedHiddenLabels, $jsonLd->hiddenLabels);
    }
}
