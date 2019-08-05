<?php

namespace CultuurNet\UDB3\UDB2\Label;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceUpdatedFromUDB2;

class LabelImporterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LabelServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $labelService;

    /**
     * @var LabelImporter
     */
    private $labelImporter;

    protected function setUp()
    {
        $this->labelService = $this->createMock(LabelServiceInterface::class);

        $this->labelImporter = new LabelImporter($this->labelService);

        $this->labelService->expects($this->at(0))
            ->method('createLabelAggregateIfNew')
            ->with(new LabelName('2dotstwice'), true);

        $this->labelService->expects($this->at(1))
            ->method('createLabelAggregateIfNew')
            ->with(new LabelName('cultuurnet'), false);
    }

    /**
     * @test
     */
    public function it_dispatches_sync_labels_commands_when_applying_event_imported_from_udb2()
    {
        $cdbXml = file_get_contents(__DIR__ . '/Samples/event.xml');
        $cdbXmlNamespaceUri = \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3');

        $eventImportedFromUDB2 = new EventImportedFromUDB2(
            'd53c2bc9-8f0e-4c9a-8457-77e8b3cab3d1',
            $cdbXml,
            $cdbXmlNamespaceUri
        );

        $this->labelImporter->handle(
            DomainMessage::recordNow(
                'd53c2bc9-8f0e-4c9a-8457-77e8b3cab3d1',
                0,
                new Metadata([]),
                $eventImportedFromUDB2
            )
        );
    }

    /**
     * @test
     */
    public function it_dispatches_label_added_commands_when_applying_place_imported_from_udb2()
    {
        $cdbXml = file_get_contents(__DIR__ . '/Samples/place.xml');
        $cdbXmlNamespaceUri = \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3');

        $placeImportedFromUDB2 = new PlaceImportedFromUDB2(
            '764066ab-826f-48c2-897d-a329ebce953f',
            $cdbXml,
            $cdbXmlNamespaceUri
        );

        $this->labelImporter->handle(
            DomainMessage::recordNow(
                '764066ab-826f-48c2-897d-a329ebce953f',
                0,
                new Metadata([]),
                $placeImportedFromUDB2
            )
        );
    }

    /**
     * @test
     */
    public function it_dispatches_label_added_commands_when_applying_organizer_imported_from_udb2()
    {
        $cdbXml = file_get_contents(__DIR__ . '/Samples/organizer.xml');
        $cdbXmlNamespaceUri = \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3');

        $organizerImportedFromUDB2 = new OrganizerImportedFromUDB2(
            '0105bc28-2368-4f89-8ea1-001c6c301065',
            $cdbXml,
            $cdbXmlNamespaceUri
        );

        $this->labelImporter->handle(
            DomainMessage::recordNow(
                '0105bc28-2368-4f89-8ea1-001c6c301065',
                0,
                new Metadata([]),
                $organizerImportedFromUDB2
            )
        );
    }

    /**
     * @test
     */
    public function it_dispatches_sync_labels_commands_when_applying_event_updated_from_udb2()
    {
        $cdbXml = file_get_contents(__DIR__ . '/Samples/event.xml');
        $cdbXmlNamespaceUri = \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3');

        $eventUpdatedFromUDB2 = new EventUpdatedFromUDB2(
            'd53c2bc9-8f0e-4c9a-8457-77e8b3cab3d1',
            $cdbXml,
            $cdbXmlNamespaceUri
        );

        $this->labelImporter->handle(
            DomainMessage::recordNow(
                'd53c2bc9-8f0e-4c9a-8457-77e8b3cab3d1',
                1,
                new Metadata([]),
                $eventUpdatedFromUDB2
            )
        );
    }

    /**
     * @test
     */
    public function it_dispatches_label_added_commands_when_applying_place_updated_from_udb2()
    {
        $cdbXml = file_get_contents(__DIR__ . '/Samples/place.xml');
        $cdbXmlNamespaceUri = \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3');

        $placeUpdatedFromUDB2 = new PlaceUpdatedFromUDB2(
            '764066ab-826f-48c2-897d-a329ebce953f',
            $cdbXml,
            $cdbXmlNamespaceUri
        );

        $this->labelImporter->handle(
            DomainMessage::recordNow(
                '764066ab-826f-48c2-897d-a329ebce953f',
                1,
                new Metadata([]),
                $placeUpdatedFromUDB2
            )
        );
    }

    /**
     * @test
     */
    public function it_dispatches_label_added_commands_when_applying_organizer_updated_from_udb2()
    {
        $cdbXml = file_get_contents(__DIR__ . '/Samples/organizer.xml');
        $cdbXmlNamespaceUri = \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3');

        $organizerUpdatedFromUDB2 = new OrganizerUpdatedFromUDB2(
            '0105bc28-2368-4f89-8ea1-001c6c301065',
            $cdbXml,
            $cdbXmlNamespaceUri
        );

        $this->labelImporter->handle(
            DomainMessage::recordNow(
                '0105bc28-2368-4f89-8ea1-001c6c301065',
                1,
                new Metadata([]),
                $organizerUpdatedFromUDB2
            )
        );
    }

    /**
     * @test
     */
    public function it_dispatches_label_added_commands_when_applying_organizer_imported_from_udb2_with_same_labels_but_different_casing()
    {
        $cdbXml = file_get_contents(__DIR__ . '/Samples/organizer_with_same_labels_different_casing.xml');
        $cdbXmlNamespaceUri = \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3');

        $organizerUpdatedFromUDB2 = new OrganizerUpdatedFromUDB2(
            '0105bc28-2368-4f89-8ea1-001c6c301065',
            $cdbXml,
            $cdbXmlNamespaceUri
        );

        $this->labelImporter->handle(
            DomainMessage::recordNow(
                '0105bc28-2368-4f89-8ea1-001c6c301065',
                1,
                new Metadata([]),
                $organizerUpdatedFromUDB2
            )
        );
    }

    /**
     * @test
     */
    public function it_dispatches_label_added_commands_when_applying_organizer_imported_from_udb2_with_same_labels_but_different_casing_and_visibility()
    {
        $cdbXml = file_get_contents(__DIR__ . '/Samples/organizer_with_same_labels_different_casing_and_visibility.xml');
        $cdbXmlNamespaceUri = \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3');

        $organizerUpdatedFromUDB2 = new OrganizerUpdatedFromUDB2(
            '0105bc28-2368-4f89-8ea1-001c6c301065',
            $cdbXml,
            $cdbXmlNamespaceUri
        );

        $this->labelImporter->handle(
            DomainMessage::recordNow(
                '0105bc28-2368-4f89-8ea1-001c6c301065',
                1,
                new Metadata([]),
                $organizerUpdatedFromUDB2
            )
        );
    }
}
